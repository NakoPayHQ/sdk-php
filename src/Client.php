<?php

declare(strict_types=1);

namespace NakoPay;

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use NakoPay\Exception\ApiException;
use NakoPay\Exception\AuthenticationException;
use NakoPay\Exception\IdempotencyException;
use NakoPay\Exception\RateLimitException;
use NakoPay\Resource\Credits;
use NakoPay\Resource\Customers;
use NakoPay\Resource\Events;
use NakoPay\Resource\Invoices;
use NakoPay\Resource\Keys;
use NakoPay\Resource\PaymentLinks;
use NakoPay\Resource\Rates;
use NakoPay\Resource\Refunds;
use NakoPay\Resource\Logs;
use NakoPay\Resource\Sandbox;
use NakoPay\Resource\Subscriptions;
use NakoPay\Resource\SubscriptionPlans;
use NakoPay\Resource\Webhooks;

class Client
{
    public const DEFAULT_BASE_URL = 'https://api.nakopay.com/v1';
    public const DEFAULT_API_VERSION = '2025-04-20';
    public const VERSION = '1.0.0';

    public readonly Invoices $invoices;
    public readonly Customers $customers;
    public readonly PaymentLinks $paymentLinks;
    public readonly Webhooks $webhooks;
    public readonly Events $events;
    public readonly Rates $rates;
    public readonly Credits $credits;
    public readonly Keys $keys;
    public readonly Subscriptions $subscriptions;
    public readonly SubscriptionPlans $subscriptionPlans;
    public readonly Refunds $refunds;
    public readonly Logs $logs;
    public readonly Sandbox $sandbox;

    private readonly string $apiKey;
    private readonly string $baseUrl;
    private readonly string $apiVersion;
    private readonly int $maxRetries;
    private readonly Guzzle $http;

    /**
     * @param array{api_key?: string, base_url?: string, api_version?: string, timeout?: float, max_retries?: int, http?: Guzzle} $config
     */
    public function __construct(array $config = [])
    {
        $key = (string) ($config['api_key'] ?? getenv('NAKOPAY_SECRET_KEY') ?: '');
        if ($key === '') {
            throw new InvalidArgumentException('api_key is required');
        }
        if (str_starts_with($key, 'pk_')) {
            throw new InvalidArgumentException(
                'a publishable key (pk_*) was passed to the server SDK; use a secret key (sk_live_* or sk_test_*)'
            );
        }
        $this->apiKey = $key;
        $this->baseUrl = rtrim((string) ($config['base_url'] ?? self::DEFAULT_BASE_URL), '/');
        $this->apiVersion = (string) ($config['api_version'] ?? self::DEFAULT_API_VERSION);
        $this->maxRetries = (int) ($config['max_retries'] ?? 3);
        $timeout = (float) ($config['timeout'] ?? 30.0);
        $this->http = $config['http'] ?? new Guzzle(['timeout' => $timeout, 'http_errors' => false]);

        $this->invoices = new Invoices($this);
        $this->customers = new Customers($this);
        $this->paymentLinks = new PaymentLinks($this);
        $this->webhooks = new Webhooks($this);
        $this->events = new Events($this);
        $this->rates = new Rates($this);
        $this->credits = new Credits($this);
        $this->keys = new Keys($this);
        $this->subscriptions = new Subscriptions($this);
        $this->subscriptionPlans = new SubscriptionPlans($this);
        $this->refunds = new Refunds($this);
        $this->logs = new Logs($this);
        $this->sandbox = new Sandbox($this);
    }

    /**
     * @param array<string, mixed>|null $body
     * @param array<string, mixed>|null $query
     * @param array{idempotency_key?: string} $opts
     * @return mixed
     */
    public function request(string $method, string $path, ?array $body = null, ?array $query = null, array $opts = [])
    {
        $url = str_starts_with($path, 'http') ? $path : $this->baseUrl . $path;
        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'X-NakoPay-Version' => $this->apiVersion,
            'User-Agent' => 'nakopay-php/' . self::VERSION,
            'Accept' => 'application/json',
        ];
        $upper = strtoupper($method);
        if (!in_array($upper, ['GET', 'HEAD'], true)) {
            $headers['Idempotency-Key'] = $opts['idempotency_key'] ?? 'idem_' . bin2hex(random_bytes(16));
        }

        $options = [
            'headers' => $headers,
            'http_errors' => false,
        ];
        if ($body !== null) {
            $options['json'] = $body;
        }
        if ($query) {
            $options['query'] = array_filter($query, static fn ($v) => $v !== null);
        }

        for ($attempt = 0; $attempt <= $this->maxRetries; $attempt++) {
            try {
                $resp = $this->http->request($upper, $url, $options);
            } catch (ConnectException $e) {
                if ($attempt < $this->maxRetries) {
                    usleep((int) ($this->backoff($attempt) * 1_000_000));
                    continue;
                }
                throw new ApiException($e->getMessage(), 'connection_error', 'connection_error');
            } catch (GuzzleException | RequestException $e) {
                throw new ApiException($e->getMessage(), 'connection_error', 'connection_error');
            }

            $status = $resp->getStatusCode();
            $bodyText = (string) $resp->getBody();
            $payload = $bodyText === '' ? null : json_decode($bodyText, true);

            if ($status >= 200 && $status < 300) {
                return $payload;
            }

            $env = is_array($payload) && isset($payload['error']) && is_array($payload['error'])
                ? $payload['error']
                : ['code' => "http_$status", 'message' => $bodyText ?: $resp->getReasonPhrase()];
            if (empty($env['request_id']) && $resp->hasHeader('x-request-id')) {
                $env['request_id'] = $resp->getHeaderLine('x-request-id');
            }

            // Throw specialized exceptions for specific status codes
            if ($status === 401) {
                throw new AuthenticationException(
                    (string) ($env['message'] ?? 'Invalid API key'),
                    isset($env['request_id']) ? (string) $env['request_id'] : null,
                );
            }
            if ($status === 409 && ($env['code'] ?? '') === 'idempotency_collision') {
                throw new IdempotencyException(
                    (string) ($env['message'] ?? 'Idempotency key collision'),
                    isset($env['request_id']) ? (string) $env['request_id'] : null,
                );
            }
            if ($status === 429) {
                $retryAfter = $resp->hasHeader('retry-after')
                    ? min((float) $resp->getHeaderLine('retry-after'), 30.0)
                    : null;
                if ($attempt < $this->maxRetries) {
                    usleep((int) (($retryAfter ?? $this->backoff($attempt)) * 1_000_000));
                    continue;
                }
                throw new RateLimitException(
                    (string) ($env['message'] ?? 'Rate limited'),
                    $retryAfter,
                    isset($env['request_id']) ? (string) $env['request_id'] : null,
                );
            }
            if ($status >= 500 && $attempt < $this->maxRetries) {
                $delay = $this->backoff($attempt);
                if ($resp->hasHeader('retry-after')) {
                    $delay = min((float) $resp->getHeaderLine('retry-after'), 30.0);
                }
                usleep((int) ($delay * 1_000_000));
                continue;
            }
            throw ApiException::fromEnvelope($env, $status);
        }
        throw new ApiException('request failed');
    }

    private function backoff(int $attempt): float
    {
        $base = min(0.25 * (2 ** $attempt), 8.0);
        return max(0.05, $base + $base * 0.25 * (mt_rand() / mt_getrandmax() * 2 - 1));
    }
}
