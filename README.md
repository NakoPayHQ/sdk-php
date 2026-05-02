# nakopay/sdk

Official NakoPay SDK for PHP. Accept Bitcoin, Lightning, Litecoin, Monero and more with a Stripe-style API.

## Install

```bash
composer require nakopay/sdk
```

## Quick Start

```php
use NakoPay\Client;

$nako = new Client(['api_key' => $_ENV['NAKOPAY_SECRET_KEY']]);

$invoice = $nako->invoices->create([
    'amount' => '25.00',
    'currency' => 'USD',
    'coin' => 'BTC',
    'description' => 'Order #1234',
]);

echo $invoice['checkout_url'];
```

## Features

- Full resource coverage: invoices, customers, payment links, webhooks, events, rates, credits, keys
- Automatic retries with exponential backoff (configurable)
- Request timeouts
- Auto-generated idempotency keys for mutations
- HMAC-SHA256 webhook signature verification
- Specialized exceptions (auth, rate limit, idempotency)
- Laravel integration with service provider, config, queue-based webhooks

## Configuration

```php
$nako = new Client([
    'api_key'     => 'sk_test_...',       // Required
    'base_url'    => 'https://api.nakopay.com/v1', // Optional
    'timeout'     => 30.0,                // Seconds (default: 30)
    'max_retries' => 3,                   // Retries for 429/5xx (default: 3)
]);
```

## Error Handling

```php
use NakoPay\Exception\ApiException;
use NakoPay\Exception\AuthenticationException;
use NakoPay\Exception\RateLimitException;
use NakoPay\Exception\IdempotencyException;

try {
    $nako->invoices->create([...]);
} catch (AuthenticationException $e) {
    // Bad API key
} catch (RateLimitException $e) {
    echo "Retry after: " . $e->getRetryAfter() . "s";
} catch (IdempotencyException $e) {
    // Key reused with different body
} catch (ApiException $e) {
    echo $e->getErrorCode();  // e.g. "validation_error"
    echo $e->getParam();      // e.g. "amount"
    echo $e->getRequestId();  // e.g. "req_xyz"
}
```

## Webhook Verification

```php
use NakoPay\Webhook;
use NakoPay\Exception\SignatureVerificationException;

$payload = file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_X_NAKOPAY_SIGNATURE'] ?? '';

try {
    $event = Webhook::constructEvent($payload, $sigHeader, $_ENV['NAKOPAY_WEBHOOK_SECRET']);
} catch (SignatureVerificationException $e) {
    http_response_code(400);
    exit;
}

switch ($event->type) {
    case 'invoice.paid':
        $invoice = $event->data['object'];
        // Fulfill order...
        break;
    case 'invoice.expired':
        // Handle expiry...
        break;
}

http_response_code(200);
echo json_encode(['received' => true]);
```

## Laravel Integration

### Setup

```bash
composer require nakopay/sdk
php artisan vendor:publish --tag=nakopay-config
```

Add to `.env`:

```
NAKOPAY_SECRET_KEY=sk_test_...
NAKOPAY_WEBHOOK_SECRET=whsec_...
NAKOPAY_WEBHOOK_QUEUE=default  # Optional: queue webhook processing
```

### Usage

```php
use NakoPay\Client;

// Injected via service container
public function createInvoice(Client $nako)
{
    return $nako->invoices->create([
        'amount' => '10.00',
        'currency' => 'USD',
        'coin' => 'BTC',
    ]);
}
```

### Webhook Route

```php
// routes/api.php
Route::post('/webhooks/nakopay', \NakoPay\Laravel\WebhookController::class);
```

### Queue-Based Webhook Processing

Set `NAKOPAY_WEBHOOK_QUEUE=default` in `.env`, then listen for the event:

```php
// app/Listeners/HandleNakoPayWebhook.php
use NakoPay\Laravel\NakoPayWebhookReceived;

class HandleNakoPayWebhook
{
    public function handle(NakoPayWebhookReceived $event): void
    {
        match ($event->event->type) {
            'invoice.paid' => $this->fulfillOrder($event->event),
            'invoice.expired' => $this->handleExpiry($event->event),
            default => null,
        };
    }
}
```

Register in `EventServiceProvider`:

```php
protected $listen = [
    NakoPayWebhookReceived::class => [
        HandleNakoPayWebhook::class,
    ],
];
```

## Links

- [API Reference](https://nakopay.com/docs/api)
- [Dashboard](https://nakopay.com/dashboard)
- [GitHub](https://github.com/NakoPayHQ/sdk-php)

## License

MIT
