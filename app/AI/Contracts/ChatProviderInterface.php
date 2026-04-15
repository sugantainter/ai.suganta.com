<?php

namespace App\AI\Contracts;

interface ChatProviderInterface
{
    public function key(): string;

    /**
     * @param  array<string,mixed>  $payload
     * @param  string  $apiKey
     * @return array<string,mixed>
     */
    public function chat(array $payload, string $apiKey): array;
}
