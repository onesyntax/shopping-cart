<?php

declare(strict_types=1);

namespace App\Domain\Notification;

final class NotificationLine
{
    public function __construct(
        public readonly string $itemName,
        public readonly int $quantity,
    ) {}
}
