<?php

declare(strict_types=1);

use App\Application\Cart\AddItemToCart;
use App\Application\Cart\AddItemToCartInput;
use App\Application\Cart\RemoveItemFromCart;
use App\Application\Cart\RemoveItemFromCartInput;
use App\Infrastructure\Persistence\InMemory\InMemoryCartRepository;

beforeEach(function () {
    $this->items = standardCatalog();
    $this->carts = new InMemoryCartRepository;
    $this->addToCart = new AddItemToCart($this->items, $this->carts);
    $this->removeFromCart = new RemoveItemFromCart($this->items, $this->carts);
});

it('rejects removing an item that is not in the catalog', function () {
    $this->removeFromCart->handle(new RemoveItemFromCartInput('alice', 'Vintage typewriter'));
})->throws('The item is not in the catalog: "Vintage typewriter".');

it('rejects removing an item that is not in the cart', function () {
    $this->removeFromCart->handle(new RemoveItemFromCartInput('alice', 'Hardcover notebook'));
})->throws('The item is not in the cart: "Hardcover notebook".');

it('takes the whole line off, however many units it holds', function (int $units) {
    $this->addToCart->handle(new AddItemToCartInput('alice', 'Hardcover notebook', $units));

    $cart = $this->removeFromCart->handle(new RemoveItemFromCartInput('alice', 'Hardcover notebook'));

    expect($cart->isEmpty())->toBeTrue();
    expect($cart->total()->format())->toBe('$0.00');
})->with([1, 3]);

it('persists the removal so the emptied cart can be read back', function () {
    $this->addToCart->handle(new AddItemToCartInput('alice', 'Hardcover notebook', 3));
    $this->removeFromCart->handle(new RemoveItemFromCartInput('alice', 'Hardcover notebook'));

    expect($this->carts->forOwner('alice')->isEmpty())->toBeTrue();
});

it('leaves other lines intact', function () {
    $this->addToCart->handle(new AddItemToCartInput('alice', 'Hardcover notebook', 2));
    $this->addToCart->handle(new AddItemToCartInput('alice', 'Ballpoint pen', 1));

    $cart = $this->removeFromCart->handle(new RemoveItemFromCartInput('alice', 'Hardcover notebook'));

    expect($cart->quantityOf('Ballpoint pen'))->toBe(1);
    expect($cart->lineCount())->toBe(1);
    expect($cart->total()->format())->toBe('$50.00');
});

it('does not affect another shopper\'s cart', function () {
    $this->addToCart->handle(new AddItemToCartInput('alice', 'Hardcover notebook', 2));
    $this->addToCart->handle(new AddItemToCartInput('bob', 'Hardcover notebook', 2));

    $this->removeFromCart->handle(new RemoveItemFromCartInput('alice', 'Hardcover notebook'));

    expect($this->carts->forOwner('alice')->isEmpty())->toBeTrue();
    expect($this->carts->forOwner('bob')->quantityOf('Hardcover notebook'))->toBe(2);
    expect($this->carts->forOwner('bob')->lineCount())->toBe(1);
});
