<?php

declare(strict_types=1);

namespace App\Checkout\Domain;

use App\Foundation\Domain\Money;

interface CardPaymentGateway
{
    public function charge(Money $amount, Card $card): PaymentResult;
}
