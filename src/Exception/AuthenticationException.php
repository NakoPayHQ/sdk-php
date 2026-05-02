<?php

declare(strict_types=1);

namespace NakoPay\Exception;

class AuthenticationException extends ApiException
{
    public function __construct(string $message = 'Invalid API key', ?string $requestId = null)
    {
        parent::__construct($message, 'authentication_required', 'authentication_error', null, $requestId, 401);
    }
}
