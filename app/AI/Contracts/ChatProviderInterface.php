<?php

namespace App\AI\Contracts;

interface ChatProviderInterface
{
    public function key(): string;

    /**
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    public function chat(array $payload, string $apiKey): array;

    /**
     * @param  array<string,mixed>  $payload
     * @param  callable(string): void  $onDelta
     * @return array<string,mixed>
     */
    public function chatStream(array $payload, string $apiKey, callable $onDelta): array;
}
