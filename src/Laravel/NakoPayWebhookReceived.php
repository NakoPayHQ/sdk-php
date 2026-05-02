<?php

declare(strict_types=1);

namespace NakoPay\Laravel;

use Illuminate\Foundation\Events\Dispatchable;
use NakoPay\Event;

/**
 * Fired when a NakoPay webhook is received and verified.
 *
 * Listen for this event in your EventServiceProvider:
 *
 *   NakoPayWebhookReceived::class => [
 *       HandleInvoicePaid::class,
 *   ],
 */
class NakoPayWebhookReceived
{
    use Dispatchable;

    public function __construct(public readonly Event $event)
    {
    }
}
