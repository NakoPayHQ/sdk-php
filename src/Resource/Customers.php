<?php

declare(strict_types=1);

namespace NakoPay\Resource;

class Customers extends Resource
{
    /** @param array<string, mixed> $params */
    public function create(array $params): mixed
    {
        return $this->client->request('POST', '/customers', $params);
    }

    public function retrieve(string $id): mixed
    {
        return $this->client->request('GET', '/customers', null, ['id' => $id]);
    }

    /** @param array<string, mixed> $params */
    public function list(array $params = []): mixed
    {
        return $this->client->request('GET', '/customers', null, $params);
    }
}
