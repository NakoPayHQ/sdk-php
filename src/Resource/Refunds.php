<?php

declare(strict_types=1);

namespace NakoPay\Resource;

class Refunds extends Resource
{
    /**
     * @param array<string, mixed> $params
     * @param array{idempotency_key?: string} $opts
     * @return mixed
     */
    public function create(array $params, array $opts = [])
    {
        return $this->client->request('POST', '/refunds-create', $params, null, $opts);
    }

    public function retrieve(string $id): mixed
    {
        return $this->client->request('GET', '/refunds-get', null, ['id' => $id]);
    }

    /** @param array<string, mixed> $params */
    public function list(array $params = []): mixed
    {
        return $this->client->request('GET', '/refunds-list', null, $params);
    }

    public function cancel(string $id): mixed
    {
        return $this->client->request('POST', '/refunds-cancel', ['id' => $id]);
    }
}
