<?php

declare(strict_types=1);

namespace NakoPay\Resource;

use NakoPay\Webhook as WebhookHelper;

class Webhooks extends Resource
{
    /** @param array<string, mixed> $params */
    public function create(array $params): mixed
    {
        return $this->client->request('POST', '/webhooks-create', $params);
    }

    public function retrieve(string $id): mixed
    {
        return $this->client->request('GET', '/webhooks-get', null, ['id' => $id]);
    }

    public function list(): mixed
    {
        return $this->client->request('GET', '/webhooks-list');
    }

    /** @param array<string, mixed> $params Must include 'id'. */
    public function update(array $params): mixed
    {
        return $this->client->request('POST', '/webhooks-update', $params);
    }

    public function delete(string $id): mixed
    {
        return $this->client->request('POST', '/webhooks-delete', ['id' => $id]);
    }

    public function test(string $id): mixed
    {
        return $this->client->request('POST', '/webhooks-test', ['id' => $id]);
    }

    /**
     * Replay a previously delivered event to a webhook endpoint.
     * @param array{delivery_id?: string} $params
     */
    public function replay(string $id, array $params = []): mixed
    {
        return $this->client->request('POST', '/webhooks-replay', ['id' => $id] + $params);
    }

    /** Convenience proxy to the static signature verifier. */
    public static function constructEvent(
        string $payload,
        ?string $signatureHeader,
        string $secret,
        int $tolerance = WebhookHelper::DEFAULT_TOLERANCE,
    ): \NakoPay\Event {
        return WebhookHelper::constructEvent($payload, $signatureHeader, $secret, $tolerance);
    }
}
