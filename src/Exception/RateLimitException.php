<?php

declare(strict_types=1);

namespace NakoPay\Exception;

class RateLimitException extends ApiException
{
    private readonly ?float $retryAfter;

    public function __construct(string $message = 'Rate limited', ?float $retryAfter = null, ?string $requestId = null)
    {
        parent::__construct($message, 'rate_limited', 'rate_limit_error', null, $requestId, 429);
        $this->retryAfter = $retryAfter;
    }

    public function getRetryAfter(): ?float
    {
        return $this->retryAfter;
    }
}
