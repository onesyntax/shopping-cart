<?php

declare(strict_types=1);

namespace App\Domain\Catalog;

use App\Domain\Shared\DomainException;

final class ItemNotInCatalog extends DomainException
{
    public static function named(string $name): self
    {
        return new self("The item is not in the catalog: \"{$name}\".");
    }
}
