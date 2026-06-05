<?php

declare(strict_types=1);

namespace App\Foundation\Domain;

enum NotificationKind
{
    case OrderPaid;
    case AwaitingDepositConfirmation;
    case AwaitingPaymentOnDelivery;
    case PaymentFailed;
}
