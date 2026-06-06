<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\InMemory;

use App\Domain\Checkout\Order;
use App\Domain\Checkout\OrderRepository;

final class InMemoryOrderRepository implements OrderRepository
{
    /** @var array<string, list<Order>> */
    private array $ordersByOwner = [];

    public function save(Order $order): void
    {
        $this->ordersByOwner[$order->ownerId][] = $order;
    }

    public function latestForOwner(string $ownerId): ?Order
    {
        $orders = $this->ordersByOwner[$ownerId] ?? [];

        return $orders === [] ? null : $orders[array_key_last($orders)];
    }
}
