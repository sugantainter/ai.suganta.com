<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ValidateGrokModelsCommand extends Command
{
    protected $signature = 'ai:validate-grok-models
        {--dry-run : Show changes without writing DB}
        {--activate-available : Re-enable models found on provider}';

    protected $description = 'Validate Grok models against provider and disable unavailable IDs';

    public function handle(): int
    {
        $providerConfig = (array) config('ai.providers.grok', []);
        $baseUrl = rtrim((string) ($providerConfig['base_url'] ?? 'https://api.x.ai/v1'), '/');
        $apiKey = trim((string) ($providerConfig['api_key'] ?? ''));
        $dryRun = (bool) $this->option('dry-run');
        $activateAvailable = (bool) $this->option('activate-available');

        if ($apiKey === '') {
            $this->error('Missing GROK_API_KEY. Set it and try again.');
            return self::FAILURE;
        }

        $this->info('Fetching Grok model catalog from provider...');

        try {
            $response = Http::retry(
                (int) config('ai.request_retries', 1),
                (int) config('ai.retry_delay_ms', 200),
                throw: false
            )
                ->connectTimeout(max(2, min((int) config('ai.request_timeout_seconds', 12), 8)))
                ->timeout((int) config('ai.request_timeout_seconds', 12))
                ->withToken($apiKey)
                ->acceptJson()
                ->get($baseUrl.'/models');
        } catch (\Throwable $exception) {
            $this->error('Unable to reach Grok models endpoint: '.$exception->getMessage());
            return self::FAILURE;
        }

        if (! $response->successful()) {
            $body = trim((string) $response->body());
            $this->error("Grok models endpoint failed with status {$response->status()}.");
            if ($body !== '') {
                $this->line($body);
            }
            return self::FAILURE;
        }

        $providerModels = collect((array) data_get((array) $response->json(), 'data', []))
            ->map(static fn ($item): string => trim((string) data_get($item, 'id', '')))
            ->filter(static fn (string $id): bool => $id !== '')
            ->unique()
            ->values();

        if ($providerModels->isEmpty()) {
            $this->warn('Provider returned no model ids. No DB updates were made.');
            return self::SUCCESS;
        }

        $this->info('Provider returned '.$providerModels->count().' model id(s).');

        $table = DB::connection('ai_mysql')->table('ai_models');
        $dbRows = $table
            ->where('provider', 'grok')
            ->get(['id', 'model_key', 'is_active']);

        if ($dbRows->isEmpty()) {
            $this->warn('No Grok models found in ai_models table.');
            return self::SUCCESS;
        }

        $toDisable = [];
        $toActivate = [];

        foreach ($dbRows as $row) {
            $modelKey = trim((string) ($row->model_key ?? ''));
            if ($modelKey === '') {
                continue;
            }

            $existsOnProvider = $providerModels->contains($modelKey);
            $isActive = (bool) ($row->is_active ?? false);

            if (! $existsOnProvider && $isActive) {
                $toDisable[] = [
                    'id' => (int) $row->id,
                    'model_key' => $modelKey,
                ];
            }

            if ($activateAvailable && $existsOnProvider && ! $isActive) {
                $toActivate[] = [
                    'id' => (int) $row->id,
                    'model_key' => $modelKey,
                ];
            }
        }

        $this->line('---');
        $this->line('Will disable unavailable: '.count($toDisable));
        if ($activateAvailable) {
            $this->line('Will activate available: '.count($toActivate));
        }

        if ($dryRun) {
            if ($toDisable !== []) {
                $this->line('Disable list: '.implode(', ', array_column($toDisable, 'model_key')));
            }
            if ($activateAvailable && $toActivate !== []) {
                $this->line('Activate list: '.implode(', ', array_column($toActivate, 'model_key')));
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

        $this->info('Grok model validation complete.');
        return self::SUCCESS;
    }
}
