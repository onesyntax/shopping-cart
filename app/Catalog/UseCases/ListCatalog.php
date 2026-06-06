<?php

declare(strict_types=1);

namespace App\Catalog\UseCases;

use App\Catalog\Domain\Item;
use App\Catalog\Domain\ItemRepository;

/**
 * Lists the items available in the catalog for a shopper to browse.
 */
final class ListCatalog
{
    public function __construct(private readonly ItemRepository $items) {}

    /**
     * @return list<Item>
     */
    public function handle(): array
    {
        return $this->items->all();
    }
}
