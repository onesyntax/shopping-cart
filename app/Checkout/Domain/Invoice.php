<?php

declare(strict_types=1);

namespace App\Checkout\Domain;

use App\Foundation\Domain\Money;

final class Invoice
{
    public function __construct(
        public readonly string $reference,
        public readonly string $orderReference,
        public readonly Money $total,
    ) {}
}
