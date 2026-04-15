<?php

namespace App\AI;

use App\AI\Contracts\ChatProviderInterface;
use App\AI\Exceptions\ProviderException;

class ProviderRegistry
{
    /**
     * @var array<string,ChatProviderInterface>
     */
    private array $providers = [];

    public function add(ChatProviderInterface $provider): void
    {
        $this->providers[$provider->key()] = $provider;
    }

    public function get(string $provider): ChatProviderInterface
    {
        if (! isset($this->providers[$provider])) {
            throw ProviderException::unsupported($provider);
        }

        return $this->providers[$provider];
    }
}
