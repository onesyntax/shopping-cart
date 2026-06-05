<?php

declare(strict_types=1);

namespace App\Checkout\Domain;

/**
 * The card a shopper pays with. The Domain only needs an opaque token; the
 * gateway adapter knows how to charge it.
 */
final class Card
{
    public function __construct(public readonly string $token) {}
}
