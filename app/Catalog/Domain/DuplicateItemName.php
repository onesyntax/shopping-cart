<?php

declare(strict_types=1);

namespace App\Catalog\Domain;

use App\Foundation\Domain\DomainException;

final class DuplicateItemName extends DomainException
{
    public static function named(string $name): self
    {
        return new self("An item named \"{$name}\" already exists.");
    }
}
