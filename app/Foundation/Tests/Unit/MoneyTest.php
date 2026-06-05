<?php

declare(strict_types=1);

use App\Foundation\Domain\DomainException;
use App\Foundation\Domain\Money;

it('parses a dollar string into cents', function () {
    expect(Money::fromString('$100.00')->cents)->toBe(10000);
});

it('parses a zero amount', function () {
    expect(Money::fromString('$0.00')->cents)->toBe(0);
});

it('parses a negative amount', function () {
    expect(Money::fromString('-$10.00')->cents)->toBe(-1000);
});

it('ignores surrounding whitespace', function () {
    expect(Money::fromString(' $100.00 ')->cents)->toBe(10000);
});

it('ignores whitespace between the currency symbol and the number', function () {
    expect(Money::fromString('$ 100.00')->cents)->toBe(10000);
});

it('reads a single-digit fraction as tens of cents', function () {
    // "$1.5" is one dollar and fifty cents, not one dollar and five cents.
    expect(Money::fromString('$1.5')->cents)->toBe(150);
});

it('parses an integer amount with no fraction part', function () {
    // No decimal point: the fraction defaults to zero cents. Reading the
    // missing fraction must not warn (the array is padded to two elements).
    expect(Money::fromString('$100')->cents)->toBe(10000);
});

it('formats cents back into a dollar string', function () {
    expect(Money::ofCents(20000)->format())->toBe('$200.00');
    expect(Money::ofCents(25000)->format())->toBe('$250.00');
    expect(Money::ofCents(0)->format())->toBe('$0.00');
});

it('formats a negative amount with a leading minus', function () {
    expect(Money::ofCents(-1)->format())->toBe('-$0.01');
    expect(Money::ofCents(-12345)->format())->toBe('-$123.45');
});

it('adds and multiplies in cents', function () {
    expect(Money::ofCents(10000)->multiply(3)->cents)->toBe(30000);
    expect(Money::ofCents(20000)->add(Money::ofCents(5000))->format())->toBe('$250.00');
});

it('knows whether it is positive', function () {
    expect(Money::ofCents(1)->isPositive())->toBeTrue();
    expect(Money::ofCents(0)->isPositive())->toBeFalse();
    expect(Money::ofCents(-1)->isPositive())->toBeFalse();
});

it('rejects a malformed money string', function () {
    Money::fromString('abc');
})->throws(DomainException::class);
