<?php

declare(strict_types=1);

namespace App\Domain\Checkout;

use App\Domain\Shared\Money;

final class Invoice
{
    public function __construct(
        public readonly string $reference,
        public readonly string $orderReference,
        public readonly Money $total,
    ) {}
}
