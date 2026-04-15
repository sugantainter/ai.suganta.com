<?php

namespace App\AI\Providers;

class OpenRouterProvider extends OpenAIProvider
{
    public function key(): string
    {
        return 'openrouter';
    }
}
