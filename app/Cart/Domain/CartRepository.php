<?php

declare(strict_types=1);

namespace App\Cart\Domain;

interface CartRepository
{
    /**
     * Return the owner's cart, or a fresh empty cart if they have none yet.
     */
    public function forOwner(string $ownerId): Cart;

    public function save(Cart $cart): void;
}
