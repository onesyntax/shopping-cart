<?php

declare(strict_types=1);

use App\Domain\Catalog\Item;
use App\Domain\Shared\Money;
use App\Domain\Shared\Quantity;
use App\Infrastructure\Persistence\Eloquent\EloquentCartRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->repository = new EloquentCartRepository;
});

it('returns a fresh empty cart for an owner with none yet', function () {
    $cart = $this->repository->forOwner('shopper-1');

    expect($cart->isEmpty())->toBeTrue()
        ->and($cart->ownerId)->toBe('shopper-1');
});

it('persists a cart and reloads its lines with captured prices', function () {
    $cart = $this->repository->forOwner('shopper-1');
    $cart->addItem(new Item('Notebook', 'A4 hardcover', Money::ofCents(10000)), Quantity::of(2));
    $cart->addItem(new Item('Pen', 'Blue ink', Money::ofCents(5000)), Quantity::of(3));
    $this->repository->save($cart);

    $reloaded = $this->repository->forOwner('shopper-1');

    expect($reloaded->lineCount())->toBe(2)
        ->and($reloaded->quantityOf('Notebook'))->toBe(2)
        ->and($reloaded->quantityOf('Pen'))->toBe(3)
        ->and($reloaded->total())->toBeMoney('$350.00');
});

it('replaces the stored lines on each save', function () {
    $cart = $this->repository->forOwner('shopper-1');
    $cart->addItem(new Item('Notebook', 'A4 hardcover', Money::ofCents(10000)), Quantity::of(2));
    $this->repository->save($cart);

    $cart = $this->repository->forOwner('shopper-1');
    $cart->removeItem('Notebook');
    $this->repository->save($cart);

    expect($this->repository->forOwner('shopper-1')->isEmpty())->toBeTrue();
});

it('keeps different owners carts apart', function () {
    $one = $this->repository->forOwner('shopper-1');
    $one->addItem(new Item('Notebook', 'A4 hardcover', Money::ofCents(10000)), Quantity::of(1));
    $this->repository->save($one);

    expect($this->repository->forOwner('shopper-2')->isEmpty())->toBeTrue();
});
