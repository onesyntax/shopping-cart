<?php

declare(strict_types=1);

namespace App\Checkout\Domain;

enum PaymentMethod: string
{
    case Card = 'card';
    case BankDeposit = 'bank_deposit';
    case CashOnDelivery = 'cash_on_delivery';
    case CashOnHand = 'cash_on_hand';

    public function label(): string
    {
        return match ($this) {
            self::Card => 'card',
            self::BankDeposit => 'bank deposit',
            self::CashOnDelivery => 'cash on delivery',
            self::CashOnHand => 'cash on hand',
        };
    }
}
