<?php

namespace App\Providers;

use App\AI\ProviderRegistry;
use App\AI\Providers\AnthropicProvider;
use App\AI\Providers\GeminiProvider;
use App\AI\Providers\OpenAIProvider;
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
            $registry->add(new OpenAIProvider());
            $registry->add(new GeminiProvider());
            $registry->add(new AnthropicProvider());

            return $registry;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('ai-chat', function (Request $request) {
            $defaultLimit = (int) env('AI_API_DEFAULT_RATE_LIMIT', 60);
            $maxPerMinute = (int) $request->attributes->get('api_key_rate_limit', $defaultLimit);
            $rateKey = $request->attributes->get('api_key_id')
                ?? $request->attributes->get('tenant_id')
                ?? $request->ip();
            $by = (string) $rateKey;

            return Limit::perMinute(max(1, $maxPerMinute))->by($by);
        });
    }
}
