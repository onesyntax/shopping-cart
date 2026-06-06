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
        return $this->carts[$ownerId] ??= new Cart($ownerId);
    }

    public function save(Cart $cart): void
    {
        $this->carts[$cart->ownerId] = $cart;
    }
}
