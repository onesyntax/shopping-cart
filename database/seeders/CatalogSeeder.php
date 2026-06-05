<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Application\Catalog\AddItemToCatalog;
use App\Application\Catalog\AddItemToCatalogInput;
use App\Domain\Catalog\DuplicateItemName;
use Illuminate\Database\Seeder;

/**
 * Stocks the storefront catalog through the AddItemToCatalog use case, so the
 * same domain rules (field validation, unique names) apply as in the app.
 * Re-running the seeder skips items already present.
 */
class CatalogSeeder extends Seeder
{
    /** @var list<array{name: string, description: string, price: string}> */
    private const ITEMS = [
        ['name' => 'Hardcover notebook', 'description' => 'A4 hardcover notebook with 200 ruled pages', 'price' => '100.00'],
        ['name' => 'Ballpoint pen', 'description' => 'Blue ink ballpoint pen, pack of 5', 'price' => '50.00'],
        ['name' => 'Fountain pen', 'description' => 'Brushed-steel fountain pen with a fine nib', 'price' => '180.00'],
        ['name' => 'Leather journal', 'description' => 'Hand-stitched leather journal with refillable pages', 'price' => '240.00'],
        ['name' => 'Ink bottle', 'description' => '30ml bottle of midnight-blue fountain-pen ink', 'price' => '35.00'],
        ['name' => 'Desk blotter', 'description' => 'Felt-lined oak desk blotter, A2', 'price' => '320.00'],
        ['name' => 'Wax seal kit', 'description' => 'Brass seal, spoon and four sticks of sealing wax', 'price' => '75.00'],
        ['name' => 'Letter set', 'description' => 'Cotton-paper writing set with lined envelopes', 'price' => '60.00'],
    ];

    public function run(AddItemToCatalog $addItem): void
    {
        foreach (self::ITEMS as $item) {
            try {
                $addItem->handle(new AddItemToCatalogInput($item['name'], $item['description'], $item['price']));
            } catch (DuplicateItemName) {
                // Already stocked — seeding is idempotent.
            }
        }
    }
}
