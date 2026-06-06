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
 * Checks out a cart to be paid in cash on delivery. The order is placed to be
 * sent out and an invoice is issued, but the order is left awaiting payment
 * until the cash is collected on delivery. The cart is emptied.
 */
final class CheckoutByCashOnDelivery
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
            paymentMethod: PaymentMethod::CashOnDelivery,
            status: OrderStatus::AwaitingPaymentOnDelivery,
        );

        $this->orders->save($order);
        $this->issueInvoice($this->invoices, $this->references, $order);
        $this->emptyCart($this->carts, $cart);
        $this->notifier->notify(Notification::awaitingPaymentOnDelivery($order));

        return $order;
    }
}
