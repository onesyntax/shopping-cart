<?php

declare(strict_types=1);

namespace App\Catalog\Domain;

interface ItemRepository
{
    public function findByName(string $name): ?Item;

    public function existsByName(string $name): bool;

    /**
     * Every item in the catalog, in the order it was added.
     *
     * @return list<Item>
     */
    public function all(): array;

    /**
     * Insert or overwrite the item identified by its name.
     */
    public function save(Item $item): void;
}
