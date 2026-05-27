<?php

declare(strict_types=1);

namespace NakoPay\Resource;

class SubscriptionPlans extends Resource
{
    /** @param array<string, mixed> $params */
    public function list(array $params = []): mixed
    {
        return $this->client->request('GET', '/subscription-plans-list', null, $params);
    }
}
