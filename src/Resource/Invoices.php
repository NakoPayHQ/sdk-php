<?php

declare(strict_types=1);

namespace NakoPay\Resource;

class Invoices extends Resource
{
    /**
     * @param array<string, mixed> $params
     * @param array{idempotency_key?: string} $opts
     * @return mixed
     */
    public function create(array $params, array $opts = [])
    {
        return $this->client->request('POST', '/invoices-create', $params, null, $opts);
    }

    public function retrieve(string $id): mixed
    {
        return $this->client->request('GET', '/invoices-get', null, ['id' => $id]);
    }

    /** @param array<string, mixed> $params */
    public function list(array $params = []): mixed
    {
        return $this->client->request('GET', '/invoices-list', null, $params);
    }

    public function cancel(string $id): mixed
    {
        return $this->client->request('POST', '/invoices-cancel', ['id' => $id]);
    }
}
