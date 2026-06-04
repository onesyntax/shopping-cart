<?php

declare(strict_types=1);
use App\Domain\Catalog\Item;
use App\Domain\Shared\Money;
use App\Infrastructure\Persistence\InMemory\InMemoryItemRepository;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature tests boot the Laravel framework (HTTP, container, the bound
| Infrastructure adapters). Unit tests for the Domain and Application layers
| stay pure PHP — no TestCase — so they run fast and prove the inner layers
| have no framework dependency.
|
*/

pest()->extend(TestCase::class)
    ->beforeEach(function () {
        // Feature tests exercise controllers and views, not the asset pipeline,
        // so stub Vite rather than requiring a built manifest.
        $this->withoutVite();
    })
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeMoney', function (string $formatted) {
    expect($this->value->format())->toBe($formatted);

    return $this;
});

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
|
| Small builders for the in-memory adapters so unit tests can wire a use case
| against fakes in one line.
|
*/

function catalogItem(string $name, string $description, int $cents): Item
{
    return new Item($name, $description, Money::ofCents($cents));
}

/**
 * A catalog repository seeded with the two items used across the feature files.
 */
function standardCatalog(): InMemoryItemRepository
{
    $items = new InMemoryItemRepository;
    $items->save(catalogItem('Hardcover notebook', 'A4 hardcover notebook with 200 ruled pages', 10000));
    $items->save(catalogItem('Ballpoint pen', 'Blue ink ballpoint pen, pack of 5', 5000));

    return $items;
}
