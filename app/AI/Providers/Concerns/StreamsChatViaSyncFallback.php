<?php

namespace App\AI\Providers\Concerns;

trait StreamsChatViaSyncFallback
{
    /**
     * @param  callable(string): void  $onDelta
     * @return array<string, mixed>
     */
    public function chatStream(array $payload, string $apiKey, callable $onDelta): array
    {
        $result = $this->chat($payload, $apiKey);
        $content = (string) ($result['content'] ?? '');
        if ($content !== '') {
            $onDelta($content);
        }

        return $result;
    }
}
