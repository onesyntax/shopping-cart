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
| Browser Test Case
|--------------------------------------------------------------------------
|
| Browser tests drive a real Chromium via Playwright (Pest 4's browser
| plugin) against the storefront's actual HTML, CSS and JS. Pest serves the
| app in-process, against a persistent sqlite file.
|
| The suite runs as ONE continuous shopping session: the database is reset
| once per run, never between tests, so each test builds on the last (see
| Tests\Support\Browser\ContinuousSession). Tests therefore run in file order
| — do not randomise this suite.
|
| Unlike Feature tests, the browser loads real assets, so Vite is NOT stubbed:
| run `npm run build` (the `test:browser` composer script does this) before
| this suite so a manifest exists.
|
*/

pest()->extend(TestCase::class)
    ->beforeEach(function () {
        Tests\Support\Browser\ContinuousSession::boot($this->app);
    })
    ->in('Browser');

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
