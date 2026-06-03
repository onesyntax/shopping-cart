<?php

declare(strict_types=1);

namespace App\Domain\Checkout;

enum PaymentOutcome
{
    case Approved;
    case Declined;
    case Unreachable;
}
