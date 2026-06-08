<?php

declare(strict_types=1);

use App\Domain\Cart\Cart;
use App\Domain\Catalog\Item;
use App\Domain\Shared\Money;
use App\Domain\Shared\Quantity;

function cartItem(string $name, int $cents): Item
{
    return new Item($name, 'A description', Money::ofCents($cents));
}

it('reports zero quantity for an item not in the cart', function () {
    $cart = new Cart('alice');

    expect($cart->quantityOf('Notebook'))->toBe(0);
});

it('exposes its lines as a zero-indexed list, regardless of item names', function () {
    $cart = new Cart('alice');
    $cart->addItem(cartItem('Notebook', 10000), Quantity::of(1));
    $cart->addItem(cartItem('Pen', 5000), Quantity::of(1));

    $lines = $cart->lines();

    expect(array_is_list($lines))->toBeTrue();
    expect($lines)->toHaveCount(2);
});
