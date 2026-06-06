<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Persistence;

use App\Catalog\Domain\Item;
use App\Catalog\Domain\ItemRepository;

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
