<?php

declare(strict_types=1);

namespace App\Application\Checkout;

/**
 * Input for checkout flows that need nothing beyond the shopper: cash on
 * delivery and cash on hand.
 */
final class CheckoutInput
{
    public function __construct(public readonly string $ownerId) {}
}
