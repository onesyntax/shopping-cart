<?php

declare(strict_types=1);

namespace App\Foundation\Tests\Unit;

use App\Foundation\Domain\DomainException;
use App\Foundation\Domain\Money;
use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Property-based tests for Money. Eris needs the PHPUnit lifecycle ($this and
 * setUp), so these live in a class rather than a top-level Pest closure.
 *
 * Arithmetic generators are bounded well inside the 64-bit range so that the
 * properties test Money's behaviour, not PHP integer overflow.
 */
final class MoneyPropertiesTest extends TestCase
{
    use TestTrait;

    /**
     * Round-trip: any amount, once formatted, must parse back to the same value.
     * format()/fromString() are inverses across the whole integer-cents range.
     */
    public function test_format_and_from_string_round_trip(): void
    {
        // Scale the generator up so it reaches amounts >= $1,000.00, where
        // format() emits a thousands separator. Shrinking still drives any
        // counterexample down toward the smallest failing amount.
        $this->forAll(Generator\map(fn (int $n) => $n * 1000, Generator\int()))
            ->then(function (int $cents) {
                $money = Money::ofCents($cents);

                expect(Money::fromString($money->format())->cents)->toBe($cents);
            });
    }

    public function test_of_cents_preserves_the_value(): void
    {
        $this->forAll(Generator\int())
            ->then(fn (int $cents) => expect(Money::ofCents($cents)->cents)->toBe($cents));
    }

    public function test_addition_is_commutative(): void
    {
        $this->forAll(Generator\int(), Generator\int())
            ->then(function (int $a, int $b) {
                expect(Money::ofCents($a)->add(Money::ofCents($b))->cents)
                    ->toBe(Money::ofCents($b)->add(Money::ofCents($a))->cents);
            });
    }

    public function test_addition_is_associative(): void
    {
        $this->forAll(Generator\int(), Generator\int(), Generator\int())
            ->then(function (int $a, int $b, int $c) {
                $left = Money::ofCents($a)->add(Money::ofCents($b))->add(Money::ofCents($c));
                $right = Money::ofCents($a)->add(Money::ofCents($b)->add(Money::ofCents($c)));

                expect($left->cents)->toBe($right->cents);
            });
    }

    public function test_zero_is_the_additive_identity(): void
    {
        $this->forAll(Generator\int())
            ->then(function (int $cents) {
                $money = Money::ofCents($cents);

                expect($money->add(Money::zero())->equals($money))->toBeTrue();
                expect(Money::zero()->add($money)->equals($money))->toBeTrue();
            });
    }

    public function test_multiplication_equals_repeated_scaling_of_cents(): void
    {
        $this->forAll(Generator\choose(-1_000_000, 1_000_000), Generator\choose(-1_000, 1_000))
            ->then(function (int $cents, int $factor) {
                expect(Money::ofCents($cents)->multiply($factor)->cents)->toBe($cents * $factor);
            });
    }

    public function test_multiplying_by_zero_yields_zero(): void
    {
        $this->forAll(Generator\int())
            ->then(fn (int $cents) => expect(Money::ofCents($cents)->multiply(0)->equals(Money::zero()))->toBeTrue());
    }

    public function test_multiplying_by_one_is_the_identity(): void
    {
        $this->forAll(Generator\int())
            ->then(function (int $cents) {
                $money = Money::ofCents($cents);

                expect($money->multiply(1)->equals($money))->toBeTrue();
            });
    }

    public function test_is_positive_agrees_with_the_sign_of_cents(): void
    {
        $this->forAll(Generator\int())
            ->then(fn (int $cents) => expect(Money::ofCents($cents)->isPositive())->toBe($cents > 0));
    }

    public function test_equality_is_reflexive_and_value_based(): void
    {
        $this->forAll(Generator\int(), Generator\int())
            ->then(function (int $a, int $b) {
                expect(Money::ofCents($a)->equals(Money::ofCents($a)))->toBeTrue();
                expect(Money::ofCents($a)->equals(Money::ofCents($b)))->toBe($a === $b);
            });
    }

    public function test_misgrouped_thousands_separators_are_rejected(): void
    {
        // "<1-3 digits>,<1-2 digit group>.00" — a comma followed by a group
        // that is not exactly three digits is never valid grouping.
        $this->forAll(Generator\choose(1, 999), Generator\choose(0, 99))
            ->then(function (int $head, int $shortGroup) {
                $text = sprintf('$%d,%d.00', $head, $shortGroup);

                expect(fn () => Money::fromString($text))->toThrow(DomainException::class);
            });
    }

    public function test_parsing_a_plain_decimal_string_yields_the_expected_cents(): void
    {
        // Build "<dollars>.<cc>" from non-negative parts and check the cents maths.
        $this->forAll(Generator\nat(), Generator\choose(0, 99))
            ->then(function (int $dollars, int $cents) {
                $text = sprintf('$%d.%02d', $dollars, $cents);

                expect(Money::fromString($text)->cents)->toBe($dollars * 100 + $cents);
            });
    }
}
