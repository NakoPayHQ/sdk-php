<?php

declare(strict_types=1);

namespace NakoPay\Exception;

class IdempotencyException extends ApiException
{
    public function __construct(string $message = 'Idempotency key collision', ?string $requestId = null)
    {
        parent::__construct($message, 'idempotency_collision', 'invalid_request_error', null, $requestId, 409);
    }
}
