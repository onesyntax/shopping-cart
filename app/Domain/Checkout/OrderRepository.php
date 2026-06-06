<?php

declare(strict_types=1);

namespace App\Domain\Checkout;

interface OrderRepository
{
    public function save(Order $order): void;

    public function latestForOwner(string $ownerId): ?Order;
}
