<?php

declare(strict_types=1);

namespace NakoPay\Resource;

use NakoPay\Client;

class Topups extends Resource
{
    /** @param array<string, mixed> $params */
    public function create(array $params): mixed
    {
        return $this->client->request('POST', '/credits-topup-create', $params);
    }

    public function retrieve(string $id): mixed
    {
        return $this->client->request('GET', '/credits-topup-status', null, ['id' => $id]);
    }
}

class Credits extends Resource
{
    public readonly Topups $topups;

    public function __construct(Client $client)
    {
        parent::__construct($client);
        $this->topups = new Topups($client);
    }

    public function balance(): mixed
    {
        return $this->client->request('GET', '/credits-balance');
    }
}
