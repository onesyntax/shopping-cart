<?php

declare(strict_types=1);

namespace App\Domain\Checkout;

use App\Domain\Shared\Money;

interface CardPaymentGateway
{
    public function charge(Money $amount, Card $card): PaymentResult;
}
