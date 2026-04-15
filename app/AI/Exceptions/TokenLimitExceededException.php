<?php

namespace App\AI\Exceptions;

use RuntimeException;

class TokenLimitExceededException extends RuntimeException
{
    public function __construct(int $limit, int $used)
    {
        parent::__construct("Token limit exceeded. Limit: {$limit}, used: {$used}.");
    }
}
