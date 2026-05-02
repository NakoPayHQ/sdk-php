<?php

declare(strict_types=1);

namespace NakoPay\Laravel;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use NakoPay\Event;

/**
 * Queued webhook processing job.
 *
 * Listen for this job in your application by extending it or using
 * Laravel's event system with the NakoPayWebhookReceived event.
 */
class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    /** @param array<string, mixed> $payload The raw event data. */
    public function __construct(public readonly array $payload)
    {
    }

    public function handle(): void
    {
        $event = new Event($this->payload);

        // Fire a Laravel event so listeners can handle it
        if (function_exists('event')) {
            event(new NakoPayWebhookReceived($event));
        }
    }
}
