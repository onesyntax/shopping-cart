<?php

declare(strict_types=1);

namespace App\Checkout\Domain;

enum OrderStatus: string
{
    /** Payment has been collected; the order is settled. */
    case Paid = 'paid';

    /** Bank deposit recorded but not yet verified against the bank. */
    case AwaitingDepositConfirmation = 'awaiting_deposit_confirmation';

    /** Order sent out; cash to be collected on delivery. */
    case AwaitingPaymentOnDelivery = 'awaiting_payment_on_delivery';
}
