# Changelog

## [1.0.0] - 2026-05-27

### Changed
- Production release version 1.0.0

## [0.2.0] - 2026-05-17

### Added
- Retry + timeout support
- Laravel queue-based webhook processing (`ProcessWebhookJob`, `NakoPayWebhookReceived` event)
- `AuthenticationException`, `RateLimitException`, `IdempotencyException`
- `X-NakoPay-Version: 2025-04-20` header on all requests
- Idempotency key auto-generation for POST requests
- Full test suite with mocked HTTP (20 tests)
- `Subscriptions` resource - list, cancel, pause, resume, portal
- `SubscriptionPlans` resource - list plans
- `Refunds` resource - create, retrieve, list, cancel
- `Logs` resource - list API request logs
- `Sandbox` resource - seed demo data
- `Webhook::constructEvent()` signature verification

## [0.1.0] - 2026-04-01

### Added
- Initial SDK with Client, resources (Invoices, PaymentLinks, Customers, Webhooks, Events, Credits, Keys, Rates)
- Laravel service provider and config
- Webhook signature verification
