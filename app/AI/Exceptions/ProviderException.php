<?php

namespace App\AI\Exceptions;

use RuntimeException;

class ProviderException extends RuntimeException
{
    public static function unsupported(string $provider): self
    {
        return new self("Provider [{$provider}] is not supported.");
    }
}
