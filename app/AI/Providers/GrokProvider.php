<?php

namespace App\AI\Providers;

class GrokProvider extends OpenAIProvider
{
    public function key(): string
    {
        return 'grok';
    }
}
