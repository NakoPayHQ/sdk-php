<?php

declare(strict_types=1);

namespace NakoPay\Resource;

class Subscriptions extends Resource
{
    public function retrieve(string $id): mixed
    {
        return $this->client->request('GET', '/subscriptions-list', null, ['id' => $id]);
    }

    /** @param array<string, mixed> $params */
    public function list(array $params = []): mixed
    {
        return $this->client->request('GET', '/subscriptions-list', null, $params);
    }

    public function cancel(string $id, bool $atPeriodEnd = true): mixed
    {
        return $this->client->request('POST', '/subscriptions-cancel', [
            'subscription_id' => $id,
            'at_period_end' => $atPeriodEnd,
        ]);
    }

    public function pause(string $id, ?string $token = null): mixed
    {
        $body = ['subscription_id' => $id];
        if ($token !== null) {
            $body['token'] = $token;
        }
        return $this->client->request('POST', '/subscriptions-pause', $body);
    }

    public function resume(string $id, ?string $token = null): mixed
    {
        $body = ['subscription_id' => $id];
        if ($token !== null) {
            $body['token'] = $token;
        }
        return $this->client->request('POST', '/subscriptions-resume', $body);
    }

    public function portal(string $id): mixed
    {
        return $this->client->request('POST', '/subscriptions-portal', [
            'subscription_id' => $id,
        ]);
    }
}
