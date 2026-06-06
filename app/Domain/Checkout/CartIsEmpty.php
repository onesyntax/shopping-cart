<?php

declare(strict_types=1);

namespace App\Domain\Checkout;

use App\Domain\Shared\DomainException;

final class CartIsEmpty extends DomainException
{
    public static function make(): self
    {
        return new self('The cart is empty.');
    }
}
