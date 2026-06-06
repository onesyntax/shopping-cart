<?php

declare(strict_types=1);

namespace App\Domain\Cart;

use App\Domain\Shared\DomainException;

final class ItemNotInCart extends DomainException
{
    public static function named(string $name): self
    {
        return new self("The item is not in the cart: \"{$name}\".");
    }
}
