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
use App\Domain\Checkout\OrderRepository;
use App\Domain\Notification\Notification;
use App\Domain\Notification\Notifier;
use App\Domain\Shared\ReferenceGenerator;

/**
 * The shared shape of every checkout: load the shopper's cart, refuse an empty
 * one, snapshot its lines into an order, then place that order — persist it,
 * optionally invoice it, empty the cart, and notify the shopper.
 *
 * Each concrete use case still owns its own payment flow and the resulting
 * status/method/notification; this base owns only the mechanics they all share,
 * so that sequence lives in exactly one place.
 */
abstract class Checkout
{
    public function __construct(
        protected readonly CartRepository $carts,
        protected readonly OrderRepository $orders,
        protected readonly Notifier $notifier,
        protected readonly ReferenceGenerator $references,
    ) {}

    /**
     * The shopper's cart, guaranteed non-empty.
     */
    protected function cartFor(string $ownerId): Cart
    {
        $cart = $this->carts->forOwner($ownerId);
        if ($cart->isEmpty()) {
            throw CartIsEmpty::make();
        }

        return $cart;
    }

    protected function newOrderReference(): string
    {
        return $this->references->next('ORD');
    }

    /**
     * @return list<OrderLine>
     */
    protected function snapshotLines(Cart $cart): array
    {
        return OrderLine::fromCartLines($cart->lines());
    }

    /**
     * Place a built order: persist it, optionally issue an invoice, empty the
     * cart, and notify the shopper. The notification is produced from the saved
     * order via the given factory (e.g. `Notification::orderPaid(...)`).
     *
     * @param  callable(Order): Notification  $notification
     */
    protected function place(Cart $cart, Order $order, ?InvoiceRepository $invoices, callable $notification): Order
    {
        $this->orders->save($order);

        if ($invoices !== null) {
            $invoices->save(new Invoice($this->references->next('INV'), $order->reference, $order->total()));
        }

        $cart->clear();
        $this->carts->save($cart);

        $this->notifier->notify($notification($order));

        return $order;
    }
}
