<?php

declare(strict_types=1);

namespace NakoPay\Resource;

class Keys extends Resource
{
    /** @param array<string, mixed> $params */
    public function create(array $params): mixed
    {
        return $this->client->request('POST', '/keys-create', $params);
    }

    public function list(): mixed
    {
        return $this->client->request('GET', '/keys-list');
    }

    public function revoke(string $id): mixed
    {
        return $this->client->request('POST', '/keys-revoke', ['id' => $id]);
    }

    public function rotate(string $id): mixed
    {
        return $this->client->request('POST', '/keys-rotate', ['id' => $id]);
    }
}
