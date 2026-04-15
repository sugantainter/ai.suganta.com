<?php

namespace App\AI\Exceptions;

use RuntimeException;

class TooManyConcurrentRequestsException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Too many concurrent chat requests. Please retry in a moment.');
    }
}
