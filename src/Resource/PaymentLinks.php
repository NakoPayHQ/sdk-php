<?php

declare(strict_types=1);

namespace NakoPay\Resource;

class PaymentLinks extends Resource
{
    /** @param array<string, mixed> $params */
    public function create(array $params): mixed
    {
        return $this->client->request('POST', '/payment-links', $params);
    }

    public function retrieve(string $id): mixed
    {
        return $this->client->request('GET', '/payment-links', null, ['id' => $id]);
    }

    /** @param array<string, mixed> $params */
    public function list(array $params = []): mixed
    {
        return $this->client->request('GET', '/payment-links', null, $params);
    }
}
