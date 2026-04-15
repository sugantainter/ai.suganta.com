<?php

namespace App\AI\Providers;

class PerplexityProvider extends OpenAIProvider
{
    public function key(): string
    {
        return 'perplexity';
    }
}
