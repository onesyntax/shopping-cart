<?php

declare(strict_types=1);

namespace App\Application\Checkout;

use App\Domain\Cart\Cart;
use App\Domain\Cart\CartRepository;
use App\Domain\Checkout\CartIsEmpty;
use App\Domain\Checkout\Invoice;
use App\Domain\Checkout\InvoiceRepository;
use App\Domain\Checkout\Order;
use App\Domain\Checkout\OrderLine;
use App\Domain\Shared\ReferenceGenerator;

/**
 * Shared mechanics for the checkout use cases: guarding against an empty cart,
 * snapshotting its lines into an order, issuing invoices, and emptying the cart.
 * Each concrete use case still owns its own payment flow and resulting status.
 */
trait IssuesOrders
{
    private function guardNotEmpty(Cart $cart): void
    {
        if ($cart->isEmpty()) {
            throw CartIsEmpty::make();
        }
    }

    /**
     * @return list<OrderLine>
     */
    private function snapshotLines(Cart $cart): array
    {
        return OrderLine::fromCartLines($cart->lines());
    }

    private function newOrderReference(ReferenceGenerator $references): string
    {
        return $references->next('ORD');
    }

    private function issueInvoice(InvoiceRepository $invoices, ReferenceGenerator $references, Order $order): void
    {
        $invoices->save(new Invoice($references->next('INV'), $order->reference, $order->total()));
    }

    private function emptyCart(CartRepository $carts, Cart $cart): void
    {
        $cart->clear();
        $carts->save($cart);
    }
}
