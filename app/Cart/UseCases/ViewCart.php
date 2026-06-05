<?php

declare(strict_types=1);

namespace App\Cart\UseCases;

use App\Cart\Domain\Cart;
use App\Cart\Domain\CartRepository;

/**
 * Returns a shopper's current cart for display. An owner with no cart yet gets
 * a fresh, empty one.
 */
final class ViewCart
{
    public function __construct(private readonly CartRepository $carts) {}

    public function handle(string $ownerId): Cart
    {
        return $this->carts->forOwner($ownerId);
    }
}
