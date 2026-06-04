<?php

declare(strict_types=1);

use App\Domain\Catalog\Item;
use App\Domain\Shared\Money;

it('accepts an item with a name, description and positive price', function () {
    $item = new Item('Notebook', 'A4 hardcover', Money::ofCents(10000));

    expect($item->name)->toBe('Notebook');
});

it('rejects a name that is only whitespace', function () {
    new Item('   ', 'A description', Money::ofCents(10000));
})->throws('The item must have a name.');

it('rejects a description that is only whitespace', function () {
    new Item('Notebook', "  \t ", Money::ofCents(10000));
})->throws('The item must have a description.');

it('rejects a price that is zero or negative', function (int $cents) {
    new Item('Notebook', 'A description', Money::ofCents($cents));
})->with([0, -100])->throws('The price must be greater than zero.');
