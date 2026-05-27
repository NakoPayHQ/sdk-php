<?php

declare(strict_types=1);

namespace NakoPay\Tests;

use InvalidArgumentException;
use NakoPay\Client;
use NakoPay\Event;
use NakoPay\Exception\ApiException;
use NakoPay\Exception\AuthenticationException;
use NakoPay\Exception\IdempotencyException;
use NakoPay\Exception\RateLimitException;
use NakoPay\Exception\SignatureVerificationException;
use NakoPay\Webhook;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class SdkTest extends TestCase
{
    private function makeClient(array $responses): Client
    {
        $mock = new MockHandler($responses);
        $handler = HandlerStack::create($mock);
        $http = new Guzzle(['handler' => $handler]);
        return new Client([
            'api_key' => 'sk_test_abc123',
            'http' => $http,
            'max_retries' => 0,
        ]);
    }

    // ---------- Constructor ----------

    public function testRejectsPublishableKeys(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('publishable key');
        new Client(['api_key' => 'pk_live_abc']);
    }

    public function testRequiresApiKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Client([]);
    }

    public function testAcceptsValidSecretKey(): void
    {
        $client = $this->makeClient([]);
        $this->assertInstanceOf(Client::class, $client);
    }

    // ---------- Invoices ----------

    public function testInvoiceCreate(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode([
                'id' => 'inv_1',
                'object' => 'invoice',
                'status' => 'pending',
                'amount' => '25.00',
                'currency' => 'USD',
                'coin' => 'BTC',
            ])),
        ]);

        $invoice = $client->invoices->create([
            'amount' => '25.00',
            'currency' => 'USD',
            'coin' => 'BTC',
        ]);

        $this->assertEquals('inv_1', $invoice['id']);
        $this->assertEquals('pending', $invoice['status']);
    }

    public function testInvoiceRetrieve(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode(['id' => 'inv_1', 'status' => 'paid'])),
        ]);

        $invoice = $client->invoices->retrieve('inv_1');
        $this->assertEquals('paid', $invoice['status']);
    }

    public function testInvoiceList(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode([
                'object' => 'list',
                'data' => [['id' => 'inv_1'], ['id' => 'inv_2']],
                'has_more' => false,
            ])),
        ]);

        $result = $client->invoices->list(['limit' => 10]);
        $this->assertCount(2, $result['data']);
    }

    public function testInvoiceCancel(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode(['id' => 'inv_1', 'status' => 'canceled'])),
        ]);

        $invoice = $client->invoices->cancel('inv_1');
        $this->assertEquals('canceled', $invoice['status']);
    }

    // ---------- Specialized Errors ----------

    public function testThrowsAuthenticationException(): void
    {
        $client = $this->makeClient([
            new Response(401, [], json_encode([
                'error' => ['code' => 'invalid_api_key', 'message' => 'bad key'],
            ])),
        ]);

        $this->expectException(AuthenticationException::class);
        $client->invoices->list();
    }

    public function testThrowsIdempotencyException(): void
    {
        $client = $this->makeClient([
            new Response(409, [], json_encode([
                'error' => ['code' => 'idempotency_collision', 'message' => 'collision'],
            ])),
        ]);

        $this->expectException(IdempotencyException::class);
        $client->invoices->create(['amount' => '1', 'currency' => 'USD', 'coin' => 'BTC']);
    }

    public function testThrowsRateLimitException(): void
    {
        $client = $this->makeClient([
            new Response(429, ['retry-after' => '5'], json_encode([
                'error' => ['code' => 'rate_limited', 'message' => 'slow down'],
            ])),
        ]);

        try {
            $client->rates->retrieve();
            $this->fail('Expected RateLimitException');
        } catch (RateLimitException $e) {
            $this->assertEquals(5.0, $e->getRetryAfter());
        }
    }

    public function testThrowsApiExceptionOnValidationError(): void
    {
        $client = $this->makeClient([
            new Response(400, [], json_encode([
                'error' => [
                    'code' => 'validation_error',
                    'message' => 'amount must be > 0',
                    'param' => 'amount',
                    'request_id' => 'req_xyz',
                ],
            ])),
        ]);

        try {
            $client->invoices->create(['amount' => '0', 'currency' => 'USD', 'coin' => 'BTC']);
            $this->fail('Expected ApiException');
        } catch (ApiException $e) {
            $this->assertEquals('validation_error', $e->getErrorCode());
            $this->assertEquals('amount', $e->getParam());
            $this->assertEquals('req_xyz', $e->getRequestId());
            $this->assertEquals(400, $e->getHttpStatus());
        }
    }

    // ---------- Resources ----------

    public function testCustomers(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode(['id' => 'cus_1', 'email' => 'a@b.com'])),
        ]);
        $cus = $client->customers->create(['email' => 'a@b.com']);
        $this->assertEquals('cus_1', $cus['id']);
    }

    public function testWebhooks(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode(['id' => 'wh_1', 'url' => 'https://example.com/wh'])),
            new Response(200, [], json_encode(['object' => 'list', 'data' => [['id' => 'wh_1']]])),
            new Response(200, [], json_encode(['id' => 'wh_1', 'deleted' => true])),
        ]);
        $wh = $client->webhooks->create(['url' => 'https://example.com/wh']);
        $this->assertEquals('wh_1', $wh['id']);

        $list = $client->webhooks->list();
        $this->assertCount(1, $list['data']);

        $del = $client->webhooks->delete('wh_1');
        $this->assertTrue($del['deleted']);
    }

    public function testRates(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode([
                'object' => 'rates',
                'base' => 'BTC',
                'quotes' => ['USD' => '60000'],
            ])),
        ]);
        $rates = $client->rates->retrieve(['base' => 'BTC', 'quotes' => ['USD']]);
        $this->assertEquals('60000', $rates['quotes']['USD']);
    }

    public function testCreditsBalance(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode(['balance_sats' => '500000'])),
        ]);
        $bal = $client->credits->balance();
        $this->assertEquals('500000', $bal['balance_sats']);
    }

    public function testKeys(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode(['id' => 'key_1', 'revealed' => 'sk_test_full'])),
            new Response(200, [], json_encode(['id' => 'key_1', 'revoked' => true])),
        ]);
        $key = $client->keys->create(['name' => 'test']);
        $this->assertEquals('sk_test_full', $key['revealed']);

        $rev = $client->keys->revoke('key_1');
        $this->assertTrue($rev['revoked']);
    }

    // ---------- Webhook Signature Verification ----------

    public function testConstructEventValid(): void
    {
        $secret = 'whsec_test';
        $body = json_encode(['id' => 'evt_1', 'type' => 'invoice.paid', 'data' => ['object' => ['id' => 'inv_1']]]);
        $t = time();
        $sig = hash_hmac('sha256', "$t.$body", $secret);
        $header = "t=$t,v1=$sig";

        $event = Webhook::constructEvent($body, $header, $secret);
        $this->assertEquals('invoice.paid', $event->type);
        $this->assertEquals('evt_1', $event->id);
    }

    public function testConstructEventRejectsTampered(): void
    {
        $secret = 'whsec_test';
        $body = json_encode(['id' => 'evt_1', 'type' => 'invoice.paid']);
        $t = time();
        $sig = hash_hmac('sha256', "$t.$body", $secret);
        $header = "t=$t,v1=$sig";

        $this->expectException(SignatureVerificationException::class);
        Webhook::constructEvent($body . 'x', $header, $secret);
    }

    public function testConstructEventRejectsStale(): void
    {
        $secret = 'whsec_test';
        $body = json_encode(['id' => 'evt_1']);
        $t = time() - 9999;
        $sig = hash_hmac('sha256', "$t.$body", $secret);
        $header = "t=$t,v1=$sig";

        $this->expectException(SignatureVerificationException::class);
        $this->expectExceptionMessage('tolerance');
        Webhook::constructEvent($body, $header, $secret);
    }

    public function testConstructEventRejectsMissingHeader(): void
    {
        $this->expectException(SignatureVerificationException::class);
        $this->expectExceptionMessage('missing');
        Webhook::constructEvent('{}', null, 'whsec_test');
    }

    public function testConstructEventRejectsEmptySecret(): void
    {
        $this->expectException(SignatureVerificationException::class);
        $this->expectExceptionMessage('required');
        Webhook::constructEvent('{}', 't=1,v1=abc', '');
    }
}
