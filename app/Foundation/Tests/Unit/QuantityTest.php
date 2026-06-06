<?php

declare(strict_types=1);

use App\Foundation\Domain\Quantity;

it('accepts a whole number of at least 1', function () {
    expect(Quantity::from(3)->value)->toBe(3);
    expect(Quantity::from('2')->value)->toBe(2);
});

it('accepts a numeric string with surrounding whitespace', function () {
    expect(Quantity::from(' 5 ')->value)->toBe(5);
});

it('accepts a whole-valued float', function () {
    expect(Quantity::from(2.0)->value)->toBe(2);
});

it('rejects zero and negative quantities as below the minimum', function (int|string $raw) {
    Quantity::from($raw);
})->with([0, -1, '0', '-1'])->throws('The quantity must be at least 1.');

it('rejects fractional quantities as not whole numbers', function (float|string $raw) {
    Quantity::from($raw);
})->with([1.5, '1.5', '0.5'])->throws('The quantity must be a whole number.');

it('adds two quantities', function () {
    expect(Quantity::from(2)->plus(Quantity::from(4))->value)->toBe(6);
});
