<?php

declare(strict_types=1);

namespace App\Application\Catalog;

final class AddItemToCatalogInput
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly string $price,
    ) {}
}
