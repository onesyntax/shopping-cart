<?php

declare(strict_types=1);

namespace App\Checkout\Domain;

interface OrderRepository
{
    public function save(Order $order): void;

    public function latestForOwner(string $ownerId): ?Order;
}
