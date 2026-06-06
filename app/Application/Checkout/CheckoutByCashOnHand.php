<?php

declare(strict_types=1);

namespace App\Application\Checkout;

use App\Domain\Cart\CartRepository;
use App\Domain\Checkout\InvoiceRepository;
use App\Domain\Checkout\Order;
use App\Domain\Checkout\OrderRepository;
use App\Domain\Checkout\OrderStatus;
use App\Domain\Checkout\PaymentMethod;
use App\Domain\Notification\Notification;
use App\Domain\Notification\Notifier;
use App\Domain\Shared\ReferenceGenerator;

/**
 * Settles a cart by paying cash at the counter. The order is recorded as paid,
 * an invoice is issued, and the cart is emptied — all immediately.
 */
final class CheckoutByCashOnHand
{
    use IssuesOrders;

    public function __construct(
        private readonly CartRepository $carts,
        private readonly OrderRepository $orders,
        private readonly InvoiceRepository $invoices,
        private readonly Notifier $notifier,
        private readonly ReferenceGenerator $references,
    ) {}

    public function handle(CheckoutInput $input): Order
    {
        $cart = $this->carts->forOwner($input->ownerId);
        $this->guardNotEmpty($cart);

        $order = new Order(
            reference: $this->newOrderReference($this->references),
            ownerId: $input->ownerId,
            lines: $this->snapshotLines($cart),
            paymentMethod: PaymentMethod::CashOnHand,
            status: OrderStatus::Paid,
        );

        $this->orders->save($order);
        $this->issueInvoice($this->invoices, $this->references, $order);
        $this->emptyCart($this->carts, $cart);
        $this->notifier->notify(Notification::orderPaid($order));

        return $order;
    }
}
