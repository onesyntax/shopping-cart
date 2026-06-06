<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Persistence;

use App\Catalog\Domain\Item;
use App\Catalog\Domain\ItemRepository;
use App\Catalog\Infrastructure\Persistence\Models\CatalogItemRecord;
use App\Foundation\Domain\Money;

/**
 * Eloquent-backed catalog. Maps between {@see CatalogItemRecord} rows and the
 * framework-free {@see Item} entity; the Domain never sees Eloquent.
 */
final class EloquentItemRepository implements ItemRepository
{
    public function findByName(string $name): ?Item
    {
        $record = CatalogItemRecord::query()->where('name', $name)->first();

        return $record === null ? null : $this->toDomain($record);
    }

    public function existsByName(string $name): bool
    {
        return CatalogItemRecord::query()->where('name', $name)->exists();
    }

    /** @return list<Item> */
    public function all(): array
    {
        return CatalogItemRecord::query()
            ->orderBy('id')
            ->get()
            ->map(fn (CatalogItemRecord $record) => $this->toDomain($record))
            ->all();
    }

    public function save(Item $item): void
    {
        CatalogItemRecord::query()->updateOrCreate(
            ['name' => $item->name],
            [
                'description' => $item->description,
                'price_cents' => $item->price->cents,
            ],
        );
    }

    private function toDomain(CatalogItemRecord $record): Item
    {
        return new Item($record->name, $record->description, Money::ofCents($record->price_cents));
    }
}
