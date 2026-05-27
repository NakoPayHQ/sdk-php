<?php

declare(strict_types=1);

namespace NakoPay\Resource;

/**
 * Sandbox-only helpers. Requires a test-mode key (sk_test_*).
 */
class Sandbox extends Resource
{
    /** @param array<string, mixed> $params */
    public function seed(array $params = []): mixed
    {
        return $this->client->request('POST', '/sandbox-seed', $params);
    }
}
