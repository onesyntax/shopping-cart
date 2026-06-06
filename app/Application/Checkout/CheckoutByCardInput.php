<?php

declare(strict_types=1);

namespace App\Application\Checkout;

final class CheckoutByCardInput
{
    public function __construct(
        public readonly string $ownerId,
        public readonly string $cardToken,
    ) {}
}
