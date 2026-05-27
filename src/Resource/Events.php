<?php

declare(strict_types=1);

namespace NakoPay\Resource;

class Events extends Resource
{
    public function retrieve(string $id): mixed
    {
        return $this->client->request('GET', '/events-get', null, ['id' => $id]);
    }

    /** @param array<string, mixed> $params */
    public function list(array $params = []): mixed
    {
        return $this->client->request('GET', '/events-list', null, $params);
    }
}
