<?php

namespace App\Providers;

use App\AI\ProviderRegistry;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ProviderRegistry::class, function () {
            $registry = new ProviderRegistry();
            $adapters = (array) config('ai.adapters', []);

            foreach ($adapters as $provider => $adapterClass) {
                if (! is_string($adapterClass) || ! class_exists($adapterClass)) {
                    continue;
                }

                $registry->add(app($adapterClass));
            }

            return $registry;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $resolveRateKey = static function (Request $request): string {
            $rateKey = $request->attributes->get('api_key_id')
                ?? $request->attributes->get('tenant_id')
                ?? $request->ip();

            return (string) $rateKey;
        };

        RateLimiter::for('ai-chat', function (Request $request) use ($resolveRateKey) {
            $defaultLimit = (int) env('AI_API_DEFAULT_RATE_LIMIT', 60);
            $maxPerMinute = (int) $request->attributes->get('api_key_rate_limit', $defaultLimit);

            return Limit::perMinute(max(1, $maxPerMinute))->by($resolveRateKey($request));
        });

        RateLimiter::for('ai-chat-poll', function (Request $request) use ($resolveRateKey) {
            $defaultPollLimit = (int) env('AI_API_POLL_RATE_LIMIT', 240);

            return Limit::perMinute(max(30, $defaultPollLimit))->by($resolveRateKey($request));
        });
    }
}
