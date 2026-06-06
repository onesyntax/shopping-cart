<?php

declare(strict_types=1);

namespace App\Infrastructure\Notification;

use App\Domain\Notification\Notification;
use App\Domain\Notification\Notifier;

/**
 * Records notifications in memory so they can be inspected (e.g. by acceptance
 * tests). A production adapter would send email / push instead.
 */
final class RecordingNotifier implements Notifier
{
    /** @var list<Notification> */
    private array $sent = [];

    public function notify(Notification $notification): void
    {
        $this->sent[] = $notification;
    }

    /** @return list<Notification> */
    public function all(): array
    {
        return $this->sent;
    }

    public function lastFor(string $recipient): ?Notification
    {
        foreach (array_reverse($this->sent) as $notification) {
            if ($notification->recipient === $recipient) {
                return $notification;
            }
        }

        return null;
    }
}
