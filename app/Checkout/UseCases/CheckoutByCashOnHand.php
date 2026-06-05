<?php

declare(strict_types=1);

namespace App\Checkout\UseCases;

use App\Checkout\Domain\Order;
use App\Checkout\Domain\OrderStatus;
use App\Checkout\Domain\PaymentMethod;
use App\Checkout\UseCases\Inputs\CheckoutInput;
use App\Foundation\Domain\Notification;

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
