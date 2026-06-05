<?php

declare(strict_types=1);

namespace App\Checkout\Domain;

use App\Foundation\Domain\DomainException;

final class CartIsEmpty extends DomainException
{
    public static function make(): self
    {
        return new self('The cart is empty.');
    }
}
