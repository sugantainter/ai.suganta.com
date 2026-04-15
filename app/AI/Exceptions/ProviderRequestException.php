<?php

namespace App\AI\Exceptions;

use RuntimeException;

class ProviderRequestException extends RuntimeException
{
    public function __construct(
        public readonly string $provider,
        public readonly int $statusCode,
        string $message
    ) {
        parent::__construct($message);
    }

    public function clientMessage(): string
    {
        return match ($this->statusCode) {
            400 => ucfirst($this->provider).' request is invalid for the selected model.',
            401, 403 => ucfirst($this->provider).' API key is invalid or unauthorized.',
            402 => ucfirst($this->provider).' billing/quota issue. Please check credits or plan.',
            404 => ucfirst($this->provider).' model or endpoint not found.',
            429 => ucfirst($this->provider).' rate limit exceeded. Please retry shortly.',
            default => ucfirst($this->provider).' request failed. Please try again.',
        };
    }

    public function errorCode(): string
    {
        return match ($this->statusCode) {
            400 => 'provider_bad_request',
            401, 403 => 'provider_auth_failed',
            402 => 'provider_billing_required',
            404 => 'provider_not_found',
            429 => 'provider_rate_limited',
            default => 'provider_request_failed',
        };
    }
}
