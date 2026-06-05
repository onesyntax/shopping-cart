<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\InMemory;

use App\Domain\Catalog\Item;
use App\Domain\Catalog\ItemRepository;

final class InMemoryItemRepository implements ItemRepository
{
    /** @var array<string, Item> */
    private array $items = [];

    public function findByName(string $name): ?Item
    {
        return $this->items[$name] ?? null;
    }

    public function existsByName(string $name): bool
    {
        return isset($this->items[$name]);
    }

    /** @return list<Item> */
    public function all(): array
    {
        return array_values($this->items);
    }

    public function save(Item $item): void
    {
        $this->items[$item->name] = $item;
    }
}
