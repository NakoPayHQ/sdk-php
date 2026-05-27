<?php

declare(strict_types=1);

namespace NakoPay\Resource;

class Logs extends Resource
{
    /** @param array<string, mixed> $params */
    public function list(array $params = []): mixed
    {
        return $this->client->request('GET', '/logs-list', null, $params);
    }
}
