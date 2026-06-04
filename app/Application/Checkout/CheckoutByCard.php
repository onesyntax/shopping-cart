<?php

declare(strict_types=1);

namespace App\Application\Checkout;

use App\Domain\Cart\CartRepository;
use App\Domain\Checkout\Card;
use App\Domain\Checkout\CardPaymentGateway;
use App\Domain\Checkout\InvoiceRepository;
use App\Domain\Checkout\Order;
use App\Domain\Checkout\OrderRepository;
use App\Domain\Checkout\OrderStatus;
use App\Domain\Checkout\PaymentDeclined;
use App\Domain\Checkout\PaymentGatewayUnreachable;
use App\Domain\Checkout\PaymentMethod;
use App\Domain\Notification\Notification;
use App\Domain\Notification\Notifier;
use App\Domain\Shared\ReferenceGenerator;

/**
 * Checks out a cart paying by card. Only a successful charge places an order,
 * issues an invoice, and empties the cart. A declined card or an unreachable
 * gateway places no order, leaves the cart untouched, and notifies the shopper
 * why the payment did not go through.
 */
final class CheckoutByCard extends InvoicedCheckout
{
    public function __construct(
        CartRepository $carts,
        OrderRepository $orders,
        InvoiceRepository $invoices,
        private readonly CardPaymentGateway $gateway,
        Notifier $notifier,
        ReferenceGenerator $references,
    ) {
        parent::__construct($carts, $orders, $invoices, $notifier, $references);
    }

    public function handle(CheckoutByCardInput $input): Order
    {
        $cart = $this->cartFor($input->ownerId);

        $result = $this->gateway->charge($cart->total(), new Card($input->cardToken));

        if ($result->isDeclined()) {
            $this->notifier->notify(Notification::paymentFailed($input->ownerId, 'her card was declined'));
            throw PaymentDeclined::make();
        }

        if ($result->isUnreachable()) {
            $this->notifier->notify(Notification::paymentFailed($input->ownerId, 'her bank could not be reached'));
            throw PaymentGatewayUnreachable::make();
        }

        $order = new Order(
            reference: $this->newOrderReference(),
            ownerId: $input->ownerId,
            lines: $this->snapshotLines($cart),
            paymentMethod: PaymentMethod::Card,
            status: OrderStatus::Paid,
            paymentReference: $result->reference,
        );

        return $this->place($cart, $order, $this->invoices, Notification::orderPaid(...));
    }
}
