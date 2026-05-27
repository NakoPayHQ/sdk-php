<?php

declare(strict_types=1);

namespace NakoPay\Laravel;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use NakoPay\Exception\SignatureVerificationException;
use NakoPay\Webhook;

/**
 * Handles incoming NakoPay webhooks.
 *
 * Register in routes/api.php:
 *   Route::post('/webhooks/nakopay', \NakoPay\Laravel\WebhookController::class);
 *
 * Override handleEvent() in a subclass for custom logic, or use the queue-based
 * approach by setting NAKOPAY_WEBHOOK_QUEUE in your .env.
 */
class WebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $secret = config('nakopay.webhook_secret', '');

        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                $request->header('X-NakoPay-Signature'),
                $secret,
            );
        } catch (SignatureVerificationException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        $queue = config('nakopay.webhook_queue');
        if ($queue) {
            ProcessWebhookJob::dispatch($event->raw)->onQueue($queue);
        } else {
            $this->handleEvent($event);
        }

        return response()->json(['received' => true]);
    }

    /**
     * Override this method for inline webhook processing.
     */
    protected function handleEvent(\NakoPay\Event $event): void
    {
        // Default: no-op. Subclass and override.
    }
}
