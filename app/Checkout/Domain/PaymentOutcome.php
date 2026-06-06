<?php

declare(strict_types=1);

namespace App\Checkout\Domain;

enum PaymentOutcome
{
    case Approved;
    case Declined;
    case Unreachable;
}
