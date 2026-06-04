<?php

declare(strict_types=1);

namespace App\Application\Checkout;

use App\Domain\Checkout\Order;
use App\Domain\Checkout\OrderStatus;
use App\Domain\Checkout\PaymentMethod;
use App\Domain\Notification\Notification;

/**
 * Settles a cart by paying cash at the counter. The order is recorded as paid,
 * an invoice is issued, and the cart is emptied — all immediately.
 */
final class CheckoutByCashOnHand extends InvoicedCheckout
{
    public function handle(CheckoutInput $input): Order
    {
        $cart = $this->cartFor($input->ownerId);

        $order = new Order(
            reference: $this->newOrderReference(),
            ownerId: $input->ownerId,
            lines: $this->snapshotLines($cart),
            paymentMethod: PaymentMethod::CashOnHand,
            status: OrderStatus::Paid,
        );

        return $this->place($cart, $order, $this->invoices, Notification::orderPaid(...));
    }
}
