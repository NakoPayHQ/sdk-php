# Changelog

## [0.2.0] - 2026-05-01

### Added
- Retry + timeout support
- Laravel queue-based webhook processing (`ProcessWebhookJob`, `NakoPayWebhookReceived` event)
- `AuthenticationException`, `RateLimitException`, `IdempotencyException`
- `X-NakoPay-Version: 2025-04-20` header on all requests
- Idempotency key auto-generation for POST requests
- Full test suite with mocked HTTP

## [0.1.0] - 2026-04-01

### Added
- Initial SDK with Client, resources (Invoices, PaymentLinks, Customers, Webhooks, Events, Credits, Keys, Rates)
- Laravel service provider and config
- Webhook signature verification
