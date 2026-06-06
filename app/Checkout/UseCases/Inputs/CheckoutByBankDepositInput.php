<?php

declare(strict_types=1);

namespace App\Checkout\UseCases\Inputs;

final class CheckoutByBankDepositInput
{
    public function __construct(
        public readonly string $ownerId,
        public readonly string $depositReference,
        public readonly string $depositDate,
    ) {}
}
