<?php

declare(strict_types=1);

use App\Cart\Infrastructure\Persistence\InMemoryCartRepository;
use App\Cart\UseCases\ViewCart;
use App\Foundation\Domain\Quantity;

it('returns the owner empty cart when they have none yet', function () {
    $useCase = new ViewCart(new InMemoryCartRepository);

    $cart = $useCase->handle('shopper-1');

    expect($cart->isEmpty())->toBeTrue()
        ->and($cart->ownerId)->toBe('shopper-1');
});

it('returns the owner saved cart with its lines', function () {
    $carts = new InMemoryCartRepository;
    $cart = $carts->forOwner('shopper-1');
    $cart->addItem(catalogItem('Ballpoint pen', 'Blue ink', 5000), Quantity::of(3));
    $carts->save($cart);

    $viewed = (new ViewCart($carts))->handle('shopper-1');

    expect($viewed->quantityOf('Ballpoint pen'))->toBe(3)
        ->and($viewed->total())->toBeMoney('$150.00');
});
