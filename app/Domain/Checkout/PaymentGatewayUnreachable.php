<?php

declare(strict_types=1);

namespace App\Domain\Checkout;

use App\Domain\Shared\DomainException;

final class PaymentGatewayUnreachable extends DomainException
{
    public static function make(): self
    {
        return new self('The bank could not be reached.');
    }
}
