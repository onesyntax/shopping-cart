<?php

declare(strict_types=1);

namespace App\Domain\Notification;

interface Notifier
{
    public function notify(Notification $notification): void;
}
