<?php

namespace App\AI\Providers;

class DeepSeekProvider extends OpenAIProvider
{
    public function key(): string
    {
        return 'deepseek';
    }
}
