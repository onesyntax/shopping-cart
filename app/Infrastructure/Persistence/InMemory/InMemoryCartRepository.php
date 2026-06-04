<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\InMemory;

use App\Domain\Cart\Cart;
use App\Domain\Cart\CartRepository;

final class InMemoryCartRepository implements CartRepository
{
    /** @var array<string, Cart> */
    private array $carts = [];

    public function forOwner(string $ownerId): Cart
    {
        // Return a detached snapshot, never the stored instance: like a real
        // database-backed repository, changes only persist when save() is
        // called. Reading also never creates a row — an unknown owner gets a
        // fresh, unsaved cart.
        return isset($this->carts[$ownerId])
            ? clone $this->carts[$ownerId]
            : new Cart($ownerId);
    }

    public function save(Cart $cart): void
    {
        $this->carts[$cart->ownerId] = clone $cart;
    }
}
