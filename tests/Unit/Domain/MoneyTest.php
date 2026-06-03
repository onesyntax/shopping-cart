<?php

declare(strict_types=1);

use App\Domain\Shared\DomainException;
use App\Domain\Shared\Money;

it('parses a dollar string into cents', function () {
    expect(Money::fromString('$100.00')->cents)->toBe(10000);
});

it('parses a zero amount', function () {
    expect(Money::fromString('$0.00')->cents)->toBe(0);
});

it('parses a negative amount', function () {
    expect(Money::fromString('-$10.00')->cents)->toBe(-1000);
});

it('formats cents back into a dollar string', function () {
    expect(Money::ofCents(20000)->format())->toBe('$200.00');
    expect(Money::ofCents(25000)->format())->toBe('$250.00');
    expect(Money::ofCents(0)->format())->toBe('$0.00');
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
