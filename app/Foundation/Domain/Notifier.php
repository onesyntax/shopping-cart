<?php

declare(strict_types=1);

namespace App\Foundation\Domain;

interface Notifier
{
    public function notify(Notification $notification): void;
}
