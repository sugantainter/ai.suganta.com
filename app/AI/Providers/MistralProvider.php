<?php

namespace App\AI\Providers;

class MistralProvider extends OpenAIProvider
{
    public function key(): string
    {
        return 'mistral';
    }
}
