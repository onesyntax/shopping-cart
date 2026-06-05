<?php

declare(strict_types=1);

namespace App\Application\Cart;

use App\Domain\Cart\Cart;
use App\Domain\Cart\CartRepository;

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
