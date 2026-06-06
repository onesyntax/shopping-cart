<?php

declare(strict_types=1);

namespace App\Foundation\Tests\Support;

use App\Cart\Domain\Cart;
use App\Catalog\Domain\Item;
use App\Foundation\Domain\Money;
use App\Foundation\Domain\Quantity;
use Eris\Generator;

/**
 * Shared basket scaffolding for the domain property-based tests.
 *
 * A "basket" is a randomly generated list of [itemIndex, quantity] operations.
 * Each item index maps deterministically to a single price, so a repeated name
 * always carries the same unit price — which lets a test state a cart's total
 * as a clean sum over the operations. Used by both the Cart and Checkout
 * property suites so they generate carts the same way.
 */
trait GeneratesBaskets
{
    private const NAME_POOL = 5;

    /** Generates a basket: a variable-length list of [itemIndex, quantity]. */
    private function basket(): Generator\SequenceGenerator
    {
        return Generator\seq(Generator\tuple(
            Generator\choose(0, self::NAME_POOL - 1),
            Generator\choose(1, 100),
        ));
    }

    private function priceCentsFor(int $index): int
    {
        return ($index + 1) * 100;
    }

    private function itemFor(int $index): Item
    {
        return new Item("item-{$index}", 'a description', Money::ofCents($this->priceCentsFor($index)));
    }

    /** @param list<array{0:int,1:int}> $ops */
    private function cartFrom(array $ops): Cart
    {
        $cart = new Cart('owner');
        foreach ($ops as [$index, $quantity]) {
            $cart->addItem($this->itemFor($index), Quantity::of($quantity));
        }

        return $cart;
    }
}
