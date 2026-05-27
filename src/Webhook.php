<?php

declare(strict_types=1);

namespace NakoPay;

use NakoPay\Exception\SignatureVerificationException;

/**
 * Lightweight value object exposing top-level event keys as properties.
 */
class Event
{
    public readonly string $id;
    public readonly string $type;
    public readonly string $apiVersion;
    public readonly int $created;
    public readonly bool $livemode;
    /** @var array<string, mixed> */
    public readonly array $data;
    /** @var array<string, mixed> */
    public readonly array $raw;

    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(array $raw)
    {
        $this->raw = $raw;
        $this->id = (string) ($raw['id'] ?? '');
        $this->type = (string) ($raw['type'] ?? '');
        $this->apiVersion = (string) ($raw['api_version'] ?? '');
        $this->created = (int) ($raw['created'] ?? 0);
        $this->livemode = (bool) ($raw['livemode'] ?? false);
        /** @var array<string, mixed> $data */
        $data = is_array($raw['data'] ?? null) ? $raw['data'] : [];
        $this->data = $data;
    }
}

class Webhook
{
    public const DEFAULT_TOLERANCE = 300;

    /**
     * Verify a signature header and return the parsed Event.
     *
     * Header format:  `t=<unix>,v1=<hex_hmac_sha256>`
     * Signed string:  `{t}.{rawBody}`
     *
     * @throws SignatureVerificationException
     */
    public static function constructEvent(
        string $payload,
        ?string $signatureHeader,
        string $secret,
        int $tolerance = self::DEFAULT_TOLERANCE,
    ): Event {
        if ($signatureHeader === null || $signatureHeader === '') {
            throw new SignatureVerificationException('missing X-NakoPay-Signature header');
        }
        if ($secret === '') {
            throw new SignatureVerificationException('webhook secret is required');
        }

        $parts = [];
        foreach (explode(',', $signatureHeader) as $kv) {
            $i = strpos($kv, '=');
            if ($i === false) continue;
            $parts[trim(substr($kv, 0, $i))] = trim(substr($kv, $i + 1));
        }
        if (!isset($parts['t'], $parts['v1']) || !ctype_digit($parts['t'])) {
            throw new SignatureVerificationException('malformed signature header');
        }

        $t = (int) $parts['t'];
        if (abs(time() - $t) > $tolerance) {
            throw new SignatureVerificationException(
                "timestamp $t outside tolerance window of {$tolerance}s"
            );
        }

        $expected = hash_hmac('sha256', "$t." . $payload, $secret);
        if (!hash_equals($expected, $parts['v1'])) {
            throw new SignatureVerificationException('signature does not match expected value');
        }

        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new SignatureVerificationException('webhook payload is not valid JSON');
        }
        return new Event($decoded);
    }
}
