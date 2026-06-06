<?php

declare(strict_types=1);

namespace App\Foundation\Tests\Unit;

use App\Foundation\Domain\DomainException;
use App\Foundation\Domain\Quantity;
use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Property-based tests for Quantity. Eris needs the PHPUnit lifecycle, so these
 * live in a class rather than a top-level Pest closure. Valid quantities are
 * whole numbers >= 1; addition stays well inside the 64-bit range.
 */
final class QuantityPropertiesTest extends TestCase
{
    use TestTrait;

    public function test_of_preserves_any_value_at_or_above_one(): void
    {
        $this->forAll(Generator\choose(1, 1_000_000))
            ->then(fn (int $n) => expect(Quantity::of($n)->value)->toBe($n));
    }

    public function test_from_an_integer_matches_of(): void
    {
        $this->forAll(Generator\choose(1, 1_000_000))
            ->then(fn (int $n) => expect(Quantity::from($n)->value)->toBe(Quantity::of($n)->value));
    }

    public function test_from_a_whole_number_string_round_trips(): void
    {
        $this->forAll(Generator\choose(1, 1_000_000))
            ->then(fn (int $n) => expect(Quantity::from((string) $n)->value)->toBe($n));
    }

    public function test_from_a_whole_valued_float_equals_from_the_integer(): void
    {
        $this->forAll(Generator\choose(1, 1_000_000))
            ->then(fn (int $n) => expect(Quantity::from((float) $n)->value)->toBe($n));
    }

    public function test_addition_equals_the_sum_of_values(): void
    {
        $this->forAll(Generator\choose(1, 1_000_000), Generator\choose(1, 1_000_000))
            ->then(function (int $a, int $b) {
                expect(Quantity::of($a)->plus(Quantity::of($b))->value)->toBe($a + $b);
            });
    }

    public function test_addition_is_commutative(): void
    {
        $this->forAll(Generator\choose(1, 1_000_000), Generator\choose(1, 1_000_000))
            ->then(function (int $a, int $b) {
                expect(Quantity::of($a)->plus(Quantity::of($b))->value)
                    ->toBe(Quantity::of($b)->plus(Quantity::of($a))->value);
            });
    }

    public function test_addition_is_associative(): void
    {
        $this->forAll(Generator\choose(1, 100_000), Generator\choose(1, 100_000), Generator\choose(1, 100_000))
            ->then(function (int $a, int $b, int $c) {
                $left = Quantity::of($a)->plus(Quantity::of($b))->plus(Quantity::of($c));
                $right = Quantity::of($a)->plus(Quantity::of($b)->plus(Quantity::of($c)));

                expect($left->value)->toBe($right->value);
            });
    }

    public function test_a_sum_is_never_smaller_than_either_operand(): void
    {
        $this->forAll(Generator\choose(1, 1_000_000), Generator\choose(1, 1_000_000))
            ->then(function (int $a, int $b) {
                $sum = Quantity::of($a)->plus(Quantity::of($b))->value;

                expect($sum)->toBeGreaterThanOrEqual($a);
                expect($sum)->toBeGreaterThanOrEqual($b);
            });
    }

    public function test_zero_and_negative_values_are_rejected(): void
    {
        $this->forAll(Generator\choose(-1_000_000, 0))
            ->then(function (int $n) {
                expect(fn () => Quantity::of($n))
                    ->toThrow(DomainException::class, 'The quantity must be at least 1.');
            });
    }

    public function test_fractional_values_are_rejected_as_non_whole(): void
    {
        // Any float with a non-zero fractional part must be refused.
        $this->forAll(Generator\suchThat(
            fn (float $f) => floor($f) !== $f,
            Generator\float()
        ))->then(function (float $fractional) {
            expect(fn () => Quantity::from($fractional))
                ->toThrow(DomainException::class, 'The quantity must be a whole number.');
        });
    }

    public function test_non_numeric_strings_are_rejected(): void
    {
        // Alphabetic strings are never valid quantities.
        $this->forAll(Generator\suchThat(
            fn (string $s) => trim($s) !== '' && ! is_numeric(trim($s)),
            Generator\string()
        ))->then(function (string $garbage) {
            expect(fn () => Quantity::from($garbage))->toThrow(DomainException::class);
        });
    }
}
