<?php

declare(strict_types=1);

namespace App\Catalog\UseCases;

use App\Catalog\Domain\DuplicateItemName;
use App\Catalog\Domain\Item;
use App\Catalog\Domain\ItemRepository;
use App\Catalog\UseCases\Inputs\AddItemToCatalogInput;
use App\Foundation\Domain\Money;

/**
 * Adds a new item to the catalog. The Item entity enforces the field rules
 * (name, description, positive price); this use case adds the cross-item rule
 * that names are unique.
 */
final class AddItemToCatalog
{
    public function __construct(private readonly ItemRepository $items) {}

    public function handle(AddItemToCatalogInput $input): Item
    {
        $item = new Item($input->name, $input->description, Money::fromString($input->price));

        if ($this->items->existsByName($item->name)) {
            throw DuplicateItemName::named($item->name);
        }

        $this->items->save($item);

        return $item;
    }
}
