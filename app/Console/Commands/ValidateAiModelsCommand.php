<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ValidateAiModelsCommand extends Command
{
    protected $signature = 'ai:validate-models
        {--dry-run : Show changes without writing DB}
        {--activate-available : Re-enable models found on provider}
        {--provider= : Only validate a single provider (e.g. grok, openai)}';

    protected $description = 'Validate ai_models rows against each provider catalog; disable unavailable ids and optionally re-enable available ones';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $activateAvailable = (bool) $this->option('activate-available');
        $onlyProvider = trim((string) $this->option('provider'));

        $adapters = array_keys((array) config('ai.adapters', []));
        sort($adapters);

        if ($onlyProvider !== '') {
            if (! in_array($onlyProvider, $adapters, true)) {
                $this->error("Unknown provider [{$onlyProvider}]. Known: ".implode(', ', $adapters));

                return self::FAILURE;
            }
            $adapters = [$onlyProvider];
        }

        /** @var array<string, Collection|null> $catalogs */
        $catalogs = [];

        foreach ($adapters as $provider) {
            $result = $this->fetchCatalogForProvider($provider);
            if ($result['skipped']) {
                $this->warn("[{$provider}] Skipped: {$result['message']}");
                $catalogs[$provider] = null;

                continue;
            }
            if (! $result['ok']) {
                $this->error("[{$provider}] {$result['message']}");
                $catalogs[$provider] = null;

                continue;
            }

            /** @var Collection $ids */
            $ids = $result['models'];
            $this->info("[{$provider}] Provider catalog: {$ids->count()} model id(s).");
            $catalogs[$provider] = $ids;
        }

        $providersWithCatalog = array_keys(array_filter(
            $catalogs,
            static fn ($c): bool => $c instanceof Collection
        ));

        if ($providersWithCatalog === []) {
            $this->warn('No provider catalogs were retrieved; no database updates were made.');

            return self::SUCCESS;
        }

        $connection = (string) config('ai.database_connection', 'ai_mysql');
        $table = DB::connection($connection)->table('ai_models');
        $dbRows = $table
            ->whereIn('provider', $providersWithCatalog)
            ->orderBy('provider')
            ->orderBy('id')
            ->get(['id', 'provider', 'model_key', 'is_active']);

        if ($dbRows->isEmpty()) {
            $this->warn('No matching rows in ai_models for the providers that returned a catalog.');

            return self::SUCCESS;
        }

        $grokAliases = $this->normalizedGrokAliases();

        $toDisable = [];
        $toActivate = [];

        foreach ($dbRows as $row) {
            $provider = trim((string) ($row->provider ?? ''));
            $modelKey = trim((string) ($row->model_key ?? ''));
            if ($provider === '' || $modelKey === '') {
                continue;
            }

            $catalog = $catalogs[$provider] ?? null;
            if (! $catalog instanceof Collection) {
                continue;
            }

            $existsOnProvider = $this->modelExistsOnCatalog($provider, $modelKey, $catalog, $grokAliases);
            $isActive = (bool) ($row->is_active ?? false);

            if (! $existsOnProvider && $isActive) {
                $toDisable[] = [
                    'id' => (int) $row->id,
                    'provider' => $provider,
                    'model_key' => $modelKey,
                ];
            }

            if ($activateAvailable && $existsOnProvider && ! $isActive) {
                $toActivate[] = [
                    'id' => (int) $row->id,
                    'provider' => $provider,
                    'model_key' => $modelKey,
                ];
            }
        }

        $this->line('---');
        $this->line('Will disable unavailable (active → inactive): '.count($toDisable));
        if ($activateAvailable) {
            $this->line('Will activate available (inactive → active): '.count($toActivate));
        }

        if ($dryRun) {
            foreach ($toDisable as $row) {
                $this->line("  [disable] {$row['provider']} / {$row['model_key']}");
            }
            if ($activateAvailable) {
                foreach ($toActivate as $row) {
                    $this->line("  [activate] {$row['provider']} / {$row['model_key']}");
                }
            }
            $this->info('Dry-run complete. No database changes made.');

            return self::SUCCESS;
        }

        $now = now();

        if ($toDisable !== []) {
            $ids = array_column($toDisable, 'id');
            $table->whereIn('id', $ids)->update([
                'is_active' => false,
                'updated_at' => $now,
            ]);
        }

        if ($activateAvailable && $toActivate !== []) {
            $ids = array_column($toActivate, 'id');
            $table->whereIn('id', $ids)->update([
                'is_active' => true,
                'updated_at' => $now,
            ]);
        }

        $this->info('Model validation complete.');

        return self::SUCCESS;
    }

    /**
     * @return array{skipped: bool, ok: bool, message: string, models: Collection}
     */
    private function fetchCatalogForProvider(string $provider): array
    {
        $config = (array) config("ai.providers.{$provider}", []);
        $apiKey = trim((string) ($config['api_key'] ?? ''));
        if ($apiKey === '') {
            return [
                'skipped' => true,
                'ok' => false,
                'message' => 'Missing API key in config.',
                'models' => collect(),
            ];
        }

        return match ($provider) {
            'gemini' => $this->fetchGeminiCatalog($config, $apiKey),
            'anthropic' => $this->fetchAnthropicCatalog($config, $apiKey),
            'cohere' => $this->fetchCohereCatalog($config, $apiKey),
            default => $this->fetchOpenAiCompatibleCatalog($provider, $config, $apiKey),
        };
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{skipped: bool, ok: bool, message: string, models: Collection}
     */
    private function fetchOpenAiCompatibleCatalog(string $provider, array $config, string $apiKey): array
    {
        $baseUrl = rtrim((string) ($config['base_url'] ?? ''), '/');
        if ($baseUrl === '') {
            return [
                'skipped' => false,
                'ok' => false,
                'message' => 'Missing base_url in config.',
                'models' => collect(),
            ];
        }

        $url = $baseUrl.'/models';

        try {
            $response = $this->httpClient()
                ->withToken($apiKey)
                ->get($url);
        } catch (\Throwable $exception) {
            return [
                'skipped' => false,
                'ok' => false,
                'message' => 'Unable to reach models endpoint: '.$exception->getMessage(),
                'models' => collect(),
            ];
        }

        if (! $response->successful()) {
            $body = trim((string) $response->body());

            return [
                'skipped' => false,
                'ok' => false,
                'message' => "Models endpoint failed with status {$response->status()}.".($body !== '' ? ' '.$body : ''),
                'models' => collect(),
            ];
        }

        $models = $this->collectIdsFromOpenAiStyleData((array) $response->json());

        return [
            'skipped' => false,
            'ok' => true,
            'message' => '',
            'models' => $models,
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{skipped: bool, ok: bool, message: string, models: Collection}
     */
    private function fetchAnthropicCatalog(array $config, string $apiKey): array
    {
        $baseUrl = rtrim((string) ($config['base_url'] ?? 'https://api.anthropic.com/v1'), '/');
        $url = $baseUrl.'/models';
        $version = (string) ($config['version'] ?? '2023-06-01');

        try {
            $response = $this->httpClient()
                ->withHeaders([
                    'x-api-key' => $apiKey,
                    'anthropic-version' => $version,
                ])
                ->get($url);
        } catch (\Throwable $exception) {
            return [
                'skipped' => false,
                'ok' => false,
                'message' => 'Unable to reach Anthropic models endpoint: '.$exception->getMessage(),
                'models' => collect(),
            ];
        }

        if (! $response->successful()) {
            $body = trim((string) $response->body());

            return [
                'skipped' => false,
                'ok' => false,
                'message' => "Anthropic models endpoint failed with status {$response->status()}.".($body !== '' ? ' '.$body : ''),
                'models' => collect(),
            ];
        }

        $models = $this->collectIdsFromOpenAiStyleData((array) $response->json());

        return [
            'skipped' => false,
            'ok' => true,
            'message' => '',
            'models' => $models,
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{skipped: bool, ok: bool, message: string, models: Collection}
     */
    private function fetchGeminiCatalog(array $config, string $apiKey): array
    {
        $baseUrl = rtrim((string) ($config['base_url'] ?? 'https://generativelanguage.googleapis.com/v1beta'), '/');
        $ids = collect();

        $pageToken = '';
        try {
            do {
                $query = [
                    'key' => $apiKey,
                    'pageSize' => 1000,
                ];
                if ($pageToken !== '') {
                    $query['pageToken'] = $pageToken;
                }

                $response = $this->httpClient()
                    ->acceptJson()
                    ->get($baseUrl.'/models', $query);

                if (! $response->successful()) {
                    $body = trim((string) $response->body());

                    return [
                        'skipped' => false,
                        'ok' => false,
                        'message' => "Gemini models endpoint failed with status {$response->status()}.".($body !== '' ? ' '.$body : ''),
                        'models' => collect(),
                    ];
                }

                $json = (array) $response->json();
                foreach ((array) data_get($json, 'models', []) as $item) {
                    $name = trim((string) data_get($item, 'name', ''));
                    if ($name === '') {
                        continue;
                    }
                    if (str_starts_with($name, 'models/')) {
                        $name = substr($name, strlen('models/'));
                    }
                    if ($name !== '') {
                        $ids->push($name);
                    }
                }

                $pageToken = trim((string) data_get($json, 'nextPageToken', ''));
            } while ($pageToken !== '');
        } catch (\Throwable $exception) {
            return [
                'skipped' => false,
                'ok' => false,
                'message' => 'Unable to reach Gemini models endpoint: '.$exception->getMessage(),
                'models' => collect(),
            ];
        }

        return [
            'skipped' => false,
            'ok' => true,
            'message' => '',
            'models' => $ids->unique()->values(),
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{skipped: bool, ok: bool, message: string, models: Collection}
     */
    private function fetchCohereCatalog(array $config, string $apiKey): array
    {
        $baseUrl = rtrim((string) ($config['base_url'] ?? 'https://api.cohere.ai/v1'), '/');

        $filtered = $this->fetchCohereCatalogPages($baseUrl, $apiKey, true);
        if (! $filtered['ok']) {
            $unfiltered = $this->fetchCohereCatalogPages($baseUrl, $apiKey, false);
            if (! $unfiltered['ok']) {
                return $filtered;
            }

            return $unfiltered;
        }

        if ($filtered['models']->isEmpty()) {
            return $this->fetchCohereCatalogPages($baseUrl, $apiKey, false);
        }

        return $filtered;
    }

    /**
     * @return array{skipped: bool, ok: bool, message: string, models: Collection}
     */
    private function fetchCohereCatalogPages(string $baseUrl, string $apiKey, bool $preferChatEndpoint): array
    {
        $ids = collect();
        $pageToken = null;

        try {
            do {
                $query = ['page_size' => 1000];
                if ($preferChatEndpoint) {
                    $query['endpoint'] = 'chat';
                }
                if ($pageToken !== null && $pageToken !== '') {
                    $query['page_token'] = $pageToken;
                }

                $response = $this->httpClient()
                    ->withToken($apiKey)
                    ->get($baseUrl.'/models', $query);

                if (! $response->successful()) {
                    $body = trim((string) $response->body());

                    return [
                        'skipped' => false,
                        'ok' => false,
                        'message' => "Cohere models endpoint failed with status {$response->status()}.".($body !== '' ? ' '.$body : ''),
                        'models' => collect(),
                    ];
                }

                $json = (array) $response->json();
                foreach ((array) data_get($json, 'models', []) as $item) {
                    $name = trim((string) data_get($item, 'name', ''));
                    if ($name !== '') {
                        $ids->push($name);
                    }
                }

                $pageToken = data_get($json, 'next_page_token');
                $pageToken = is_string($pageToken) ? trim($pageToken) : null;
            } while ($pageToken !== null && $pageToken !== '');
        } catch (\Throwable $exception) {
            return [
                'skipped' => false,
                'ok' => false,
                'message' => 'Unable to reach Cohere models endpoint: '.$exception->getMessage(),
                'models' => collect(),
            ];
        }

        return [
            'skipped' => false,
            'ok' => true,
            'message' => '',
            'models' => $ids->unique()->values(),
        ];
    }

    private function httpClient(): PendingRequest
    {
        return Http::retry(
            (int) config('ai.request_retries', 1),
            (int) config('ai.retry_delay_ms', 200),
            throw: false
        )
            ->connectTimeout(max(2, min((int) config('ai.request_timeout_seconds', 12), 8)))
            ->timeout((int) config('ai.request_timeout_seconds', 12))
            ->acceptJson();
    }

    /**
     * @param  array<string, mixed>  $json
     */
    private function collectIdsFromOpenAiStyleData(array $json): Collection
    {
        return collect((array) data_get($json, 'data', []))
            ->map(static fn ($item): string => trim((string) data_get($item, 'id', '')))
            ->filter(static fn (string $id): bool => $id !== '')
            ->unique()
            ->values();
    }

    /**
     * @return array<string, string>
     */
    private function normalizedGrokAliases(): array
    {
        $raw = (array) data_get((array) config('ai.providers.grok', []), 'model_aliases', []);

        $out = [];
        foreach ($raw as $from => $to) {
            $from = trim((string) $from);
            $to = trim((string) $to);
            if ($from !== '' && $to !== '') {
                $out[$from] = $to;
            }
        }

        return $out;
    }

    /**
     * @param  Collection<int, string>  $catalog
     */
    private function modelExistsOnCatalog(string $provider, string $modelKey, Collection $catalog, array $grokAliases): bool
    {
        if ($catalog->contains($modelKey)) {
            return true;
        }

        if ($provider === 'grok' && isset($grokAliases[$modelKey])) {
            return $catalog->contains($grokAliases[$modelKey]);
        }

        return false;
    }
}
