<?php

declare(strict_types=1);

namespace App\Domain\Catalog;

use App\Domain\Shared\DomainException;
use App\Domain\Shared\Money;

/**
 * A catalog item. Its name identifies it, so names are unique across the
 * catalog (uniqueness is enforced by the use case against the repository).
 */
final class Item
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly Money $price,
    ) {
        if (trim($name) === '') {
            throw new DomainException('The item must have a name.');
        }

        if (trim($description) === '') {
            throw new DomainException('The item must have a description.');
        }

        if (! $price->isPositive()) {
            throw new DomainException('The price must be greater than zero.');
        }
    }
}
