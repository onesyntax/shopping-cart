<?php

declare(strict_types=1);

namespace App\Domain\Catalog;

interface ItemRepository
{
    public function findByName(string $name): ?Item;

    public function existsByName(string $name): bool;

    /**
     * Insert or overwrite the item identified by its name.
     */
    public function save(Item $item): void;
}
