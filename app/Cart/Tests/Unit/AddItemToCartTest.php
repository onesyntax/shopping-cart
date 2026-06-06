<?php

declare(strict_types=1);

use App\Cart\Infrastructure\Persistence\InMemoryCartRepository;
use App\Cart\UseCases\AddItemToCart;
use App\Cart\UseCases\Inputs\AddItemToCartInput;

beforeEach(function () {
    $this->items = standardCatalog();
    $this->carts = new InMemoryCartRepository;
    $this->addToCart = new AddItemToCart($this->items, $this->carts);
});

it('rejects an item that is not in the catalog', function () {
    $this->addToCart->handle(new AddItemToCartInput('alice', 'Vintage typewriter', 1));
})->throws('The item is not in the catalog: "Vintage typewriter".');

it('rejects a quantity below 1', function (int|string $qty) {
    $this->addToCart->handle(new AddItemToCartInput('alice', 'Hardcover notebook', $qty));
})->with([0, -1])->throws('The quantity must be at least 1.');

it('rejects a fractional quantity', function () {
    $this->addToCart->handle(new AddItemToCartInput('alice', 'Hardcover notebook', 1.5));
})->throws('The quantity must be a whole number.');

it('adds a single unit to an empty cart', function () {
    $cart = $this->addToCart->handle(new AddItemToCartInput('alice', 'Hardcover notebook', 1));

    expect($cart->quantityOf('Hardcover notebook'))->toBe(1);
    expect($cart->total()->format())->toBe('$100.00');
});

it('adds several units in one go', function () {
    $cart = $this->addToCart->handle(new AddItemToCartInput('alice', 'Hardcover notebook', 3));

    expect($cart->quantityOf('Hardcover notebook'))->toBe(3);
    expect($cart->total()->format())->toBe('$300.00');
});

it('persists the cart so it can be read back from the repository', function () {
    $this->addToCart->handle(new AddItemToCartInput('alice', 'Hardcover notebook', 2));

    $stored = $this->carts->forOwner('alice');
    expect($stored->quantityOf('Hardcover notebook'))->toBe(2);
    expect($stored->total()->format())->toBe('$200.00');
});

it('merges into the existing line when the item is already in the cart', function () {
    $this->addToCart->handle(new AddItemToCartInput('alice', 'Hardcover notebook', 2));
    $cart = $this->addToCart->handle(new AddItemToCartInput('alice', 'Hardcover notebook', 4));

    expect($cart->quantityOf('Hardcover notebook'))->toBe(6);
    expect($cart->lineCount())->toBe(1);
    expect($cart->total()->format())->toBe('$600.00');
});

it('creates a new line for a different item', function () {
    $this->addToCart->handle(new AddItemToCartInput('alice', 'Hardcover notebook', 2));
    $cart = $this->addToCart->handle(new AddItemToCartInput('alice', 'Ballpoint pen', 1));

    expect($cart->quantityOf('Hardcover notebook'))->toBe(2);
    expect($cart->quantityOf('Ballpoint pen'))->toBe(1);
    expect($cart->lineCount())->toBe(2);
    expect($cart->total()->format())->toBe('$250.00');
});

it('keeps each shopper\'s cart separate', function () {
    $this->addToCart->handle(new AddItemToCartInput('alice', 'Hardcover notebook', 2));
    $this->addToCart->handle(new AddItemToCartInput('bob', 'Ballpoint pen', 1));

    expect($this->carts->forOwner('bob')->quantityOf('Ballpoint pen'))->toBe(1);
    expect($this->carts->forOwner('bob')->lineCount())->toBe(1);
    expect($this->carts->forOwner('alice')->quantityOf('Hardcover notebook'))->toBe(2);
    expect($this->carts->forOwner('alice')->lineCount())->toBe(1);
});
