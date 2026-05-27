<?php

declare(strict_types=1);

namespace NakoPay\Exception;

class ApiException extends NakoPayException
{
    public function __construct(
        string $message,
        private readonly string $errorCode = 'api_error',
        private readonly string $errorType = 'api_error',
        private readonly ?string $param = null,
        private readonly ?string $requestId = null,
        private readonly ?int $httpStatus = null,
        private readonly ?string $docUrl = null,
    ) {
        parent::__construct($message, $httpStatus ?? 0);
    }

    /**
     * @param array<string, mixed> $env
     */
    public static function fromEnvelope(array $env, ?int $httpStatus = null): self
    {
        return new self(
            (string) ($env['message'] ?? $env['code'] ?? 'unknown error'),
            (string) ($env['code'] ?? 'api_error'),
            (string) ($env['type'] ?? 'api_error'),
            isset($env['param']) ? (string) $env['param'] : null,
            isset($env['request_id']) ? (string) $env['request_id'] : null,
            $httpStatus,
            isset($env['doc_url']) ? (string) $env['doc_url'] : null,
        );
    }

    public function getErrorCode(): string { return $this->errorCode; }
    public function getErrorType(): string { return $this->errorType; }
    public function getParam(): ?string { return $this->param; }
    public function getRequestId(): ?string { return $this->requestId; }
    public function getHttpStatus(): ?int { return $this->httpStatus; }
    public function getDocUrl(): ?string { return $this->docUrl; }
}
