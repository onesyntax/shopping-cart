<?php

declare(strict_types=1);

use App\Application\Catalog\AddItemToCatalog;
use App\Application\Catalog\AddItemToCatalogInput;
use App\Infrastructure\Persistence\InMemory\InMemoryItemRepository;

beforeEach(function () {
    $this->items = new InMemoryItemRepository;
    $this->addItem = new AddItemToCatalog($this->items);
});

it('adds a new item, making it available in the catalog', function () {
    $item = $this->addItem->handle(new AddItemToCatalogInput(
        'Hardcover notebook',
        'A4 hardcover notebook with 200 ruled pages',
        '$100.00',
    ));

    expect($item->price->format())->toBe('$100.00');
    expect($this->items->findByName('Hardcover notebook')->price->format())->toBe('$100.00');
});

it('rejects an item without a name', function () {
    $this->addItem->handle(new AddItemToCatalogInput('', 'A description', '$100.00'));
})->throws('The item must have a name.');

it('rejects an item without a description', function () {
    $this->addItem->handle(new AddItemToCatalogInput('Hardcover notebook', '', '$100.00'));
})->throws('The item must have a description.');

it('rejects a zero or negative price', function (string $price) {
    $this->addItem->handle(new AddItemToCatalogInput('Hardcover notebook', 'A description', $price));
})->with(['$0.00', '-$10.00'])->throws('The price must be greater than zero.');

it('rejects a duplicate item name', function () {
    $this->items->save(catalogItem('Hardcover notebook', 'Existing listing', 10000));

    $this->addItem->handle(new AddItemToCatalogInput(
        'Hardcover notebook',
        'A second hardcover notebook listing',
        '$120.00',
    ));
})->throws('An item named "Hardcover notebook" already exists.');
