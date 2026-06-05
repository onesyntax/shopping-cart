<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Cart\Cart;
use App\Domain\Catalog\Item;
use App\Domain\Checkout\Invoice;
use App\Domain\Checkout\Order;
use App\Domain\Checkout\OrderLine;
use App\Domain\Checkout\OrderStatus;
use App\Domain\Checkout\PaymentMethod;
use App\Domain\Shared\Money;
use App\Domain\Shared\Quantity;
use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Property-based tests for the checkout snapshot (Order / OrderLine / Invoice).
 *
 * The core invariant is money conservation through checkout: an Order built
 * from a cart's lines must carry exactly the same total, line count and
 * per-item quantities as the cart it came from. Baskets are generated the same
 * way as in CartPropertiesTest — each item index maps to a single price.
 */
final class OrderPropertiesTest extends TestCase
{
    use TestTrait;

    private const NAME_POOL = 5;

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

    private function orderFrom(Cart $cart): Order
    {
        return new Order(
            reference: 'ORD-1',
            ownerId: $cart->ownerId,
            lines: OrderLine::fromCartLines($cart->lines()),
            paymentMethod: PaymentMethod::cases()[0],
            status: OrderStatus::cases()[0],
        );
    }

    public function test_order_total_equals_the_source_cart_total(): void
    {
        $this->forAll($this->basket())
            ->then(function (array $ops) {
                $cart = $this->cartFrom($ops);

                expect($this->orderFrom($cart)->total()->cents)->toBe($cart->total()->cents);
            });
    }

    public function test_order_preserves_line_count_and_per_item_quantities(): void
    {
        $this->forAll($this->basket())
            ->then(function (array $ops) {
                $cart = $this->cartFrom($ops);
                $order = $this->orderFrom($cart);

                expect($order->lineCount())->toBe($cart->lineCount());

                foreach ($cart->lines() as $line) {
                    expect($order->quantityOf($line->itemName))->toBe($cart->quantityOf($line->itemName));
                }
            });
    }

    public function test_order_total_equals_the_sum_of_its_line_subtotals(): void
    {
        $this->forAll($this->basket())
            ->then(function (array $ops) {
                $order = $this->orderFrom($this->cartFrom($ops));

                $expected = 0;
                foreach ($order->lines as $line) {
                    $expected += $line->subtotal()->cents;
                }

                expect($order->total()->cents)->toBe($expected);
            });
    }

    public function test_order_line_subtotal_equals_price_times_quantity(): void
    {
        $this->forAll(Generator\choose(1, 1_000_000), Generator\choose(1, 1_000))
            ->then(function (int $priceCents, int $quantity) {
                $line = new OrderLine('item', Money::ofCents($priceCents), Quantity::of($quantity));

                expect($line->subtotal()->cents)->toBe($priceCents * $quantity);
            });
    }

    public function test_invoice_preserves_the_total_it_is_built_with(): void
    {
        $this->forAll(Generator\nat())
            ->then(function (int $cents) {
                $invoice = new Invoice('INV-1', 'ORD-1', Money::ofCents($cents));

                expect($invoice->total->cents)->toBe($cents);
            });
    }
}
