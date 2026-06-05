<?php

declare(strict_types=1);

use App\Domain\Catalog\Item;
use App\Domain\Shared\Money;
use App\Infrastructure\Persistence\Eloquent\EloquentItemRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->repository = new EloquentItemRepository;
});

it('saves an item and finds it by name', function () {
    $this->repository->save(new Item('Ballpoint pen', 'Blue ink, pack of 5', Money::ofCents(5000)));

    $found = $this->repository->findByName('Ballpoint pen');

    expect($found)->toBeInstanceOf(Item::class)
        ->and($found->description)->toBe('Blue ink, pack of 5')
        ->and($found->price->cents)->toBe(5000);
});

it('returns null for an unknown item', function () {
    expect($this->repository->findByName('Nothing'))->toBeNull();
});

it('reports whether an item exists by name', function () {
    $this->repository->save(new Item('Notebook', 'A4 hardcover', Money::ofCents(10000)));

    expect($this->repository->existsByName('Notebook'))->toBeTrue()
        ->and($this->repository->existsByName('Missing'))->toBeFalse();
});

it('overwrites an item saved again under the same name', function () {
    $this->repository->save(new Item('Notebook', 'Old', Money::ofCents(10000)));
    $this->repository->save(new Item('Notebook', 'New', Money::ofCents(12000)));

    expect($this->repository->all())->toHaveCount(1)
        ->and($this->repository->findByName('Notebook')->description)->toBe('New');
});

it('lists every saved item in insertion order', function () {
    $this->repository->save(new Item('Notebook', 'A4 hardcover', Money::ofCents(10000)));
    $this->repository->save(new Item('Ballpoint pen', 'Blue ink', Money::ofCents(5000)));

    expect(array_map(fn ($item) => $item->name, $this->repository->all()))
        ->toBe(['Notebook', 'Ballpoint pen']);
});
