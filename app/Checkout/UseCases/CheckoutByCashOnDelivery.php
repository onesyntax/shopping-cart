<?php

declare(strict_types=1);

namespace App\Checkout\UseCases;

use App\Checkout\Domain\Order;
use App\Checkout\Domain\OrderStatus;
use App\Checkout\Domain\PaymentMethod;
use App\Checkout\UseCases\Inputs\CheckoutInput;
use App\Foundation\Domain\Notification;

/**
 * Checks out a cart to be paid in cash on delivery. The order is placed to be
 * sent out and an invoice is issued, but the order is left awaiting payment
 * until the cash is collected on delivery. The cart is emptied.
 */
final class CheckoutByCashOnDelivery extends InvoicedCheckout
{
    public function handle(CheckoutInput $input): Order
    {
        $cart = $this->cartFor($input->ownerId);

        $order = new Order(
            reference: $this->newOrderReference(),
            ownerId: $input->ownerId,
            lines: $this->snapshotLines($cart),
            paymentMethod: PaymentMethod::CashOnDelivery,
            status: OrderStatus::AwaitingPaymentOnDelivery,
        );

        return $this->place($cart, $order, $this->invoices, Notification::awaitingPaymentOnDelivery(...));
    }
}
