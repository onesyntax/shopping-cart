<?php

declare(strict_types=1);

namespace App\Cart\Tests\Unit;

use App\Cart\Domain\Cart;
use App\Foundation\Domain\Quantity;
use App\Foundation\Tests\Support\GeneratesBaskets;
use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Property-based tests for the Cart aggregate. Cart is pure domain, so no
 * repository or framework is needed.
 *
 * A "basket" is a randomly generated list of [itemIndex, quantity] operations.
 * Each item index maps deterministically to a single price, so a repeated name
 * always carries the same unit price — which is what lets us state the total as
 * a clean sum over the operations (the cart merges repeats and keeps the price
 * captured on first add).
 */
final class CartPropertiesTest extends TestCase
{
    use GeneratesBaskets;
    use TestTrait;

    public function test_total_equals_the_sum_of_price_times_quantity(): void
    {
        $this->forAll($this->basket())
            ->then(function (array $ops) {
                $expected = 0;
                foreach ($ops as [$index, $quantity]) {
                    $expected += $this->priceCentsFor($index) * $quantity;
                }

                expect($this->cartFrom($ops)->total()->cents)->toBe($expected);
            });
    }

    public function test_line_count_equals_the_number_of_distinct_items(): void
    {
        $this->forAll($this->basket())
            ->then(function (array $ops) {
                $distinct = count(array_unique(array_map(fn ($op) => $op[0], $ops)));

                expect($this->cartFrom($ops)->lineCount())->toBe($distinct);
            });
    }

    public function test_quantity_of_an_item_equals_the_sum_of_its_added_quantities(): void
    {
        $this->forAll($this->basket())
            ->then(function (array $ops) {
                $cart = $this->cartFrom($ops);

                $expected = [];
                foreach ($ops as [$index, $quantity]) {
                    $expected[$index] = ($expected[$index] ?? 0) + $quantity;
                }

                foreach ($expected as $index => $total) {
                    expect($cart->quantityOf("item-{$index}"))->toBe($total);
                }
            });
    }

    public function test_adding_the_same_item_merges_into_one_line(): void
    {
        $this->forAll(Generator\choose(0, self::NAME_POOL - 1), Generator\choose(1, 100), Generator\choose(1, 100))
            ->then(function (int $index, int $q1, int $q2) {
                $cart = new Cart('owner');
                $cart->addItem($this->itemFor($index), Quantity::of($q1));
                $cart->addItem($this->itemFor($index), Quantity::of($q2));

                expect($cart->lineCount())->toBe(1);
                expect($cart->quantityOf("item-{$index}"))->toBe($q1 + $q2);
                expect($cart->total()->cents)->toBe($this->priceCentsFor($index) * ($q1 + $q2));
            });
    }

    public function test_total_is_independent_of_the_order_items_are_added(): void
    {
        $this->forAll($this->basket())
            ->then(function (array $ops) {
                $forward = $this->cartFrom($ops);
                $reversed = $this->cartFrom(array_reverse($ops));

                expect($reversed->total()->cents)->toBe($forward->total()->cents);
                expect($reversed->lineCount())->toBe($forward->lineCount());
            });
    }

    public function test_adding_then_removing_an_item_restores_the_cart(): void
    {
        // Use an index outside the basket's pool so the added line is always new.
        $newIndex = self::NAME_POOL + 1;

        $this->forAll($this->basket(), Generator\choose(1, 100))
            ->then(function (array $ops, int $quantity) use ($newIndex) {
                $cart = $this->cartFrom($ops);
                $totalBefore = $cart->total()->cents;
                $linesBefore = $cart->lineCount();

                $cart->addItem($this->itemFor($newIndex), Quantity::of($quantity));
                $cart->removeItem("item-{$newIndex}");

                expect($cart->total()->cents)->toBe($totalBefore);
                expect($cart->lineCount())->toBe($linesBefore);
            });
    }

    public function test_clearing_empties_the_cart_and_zeroes_the_total(): void
    {
        $this->forAll($this->basket())
            ->then(function (array $ops) {
                $cart = $this->cartFrom($ops);
                $cart->clear();

                expect($cart->isEmpty())->toBeTrue();
                expect($cart->lineCount())->toBe(0);
                expect($cart->total()->cents)->toBe(0);
            });
    }

    public function test_one_bulk_add_equals_many_single_unit_adds(): void
    {
        $this->forAll(Generator\choose(0, self::NAME_POOL - 1), Generator\choose(1, 50))
            ->then(function (int $index, int $quantity) {
                $bulk = new Cart('owner');
                $bulk->addItem($this->itemFor($index), Quantity::of($quantity));

                $oneByOne = new Cart('owner');
                for ($i = 0; $i < $quantity; $i++) {
                    $oneByOne->addItem($this->itemFor($index), Quantity::of(1));
                }

                expect($oneByOne->quantityOf("item-{$index}"))->toBe($bulk->quantityOf("item-{$index}"));
                expect($oneByOne->total()->cents)->toBe($bulk->total()->cents);
            });
    }
}
