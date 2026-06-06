<?php

declare(strict_types=1);

namespace App\Checkout\UseCases;

use App\Cart\Domain\CartRepository;
use App\Checkout\Domain\Card;
use App\Checkout\Domain\CardPaymentGateway;
use App\Checkout\Domain\InvoiceRepository;
use App\Checkout\Domain\Order;
use App\Checkout\Domain\OrderRepository;
use App\Checkout\Domain\OrderStatus;
use App\Checkout\Domain\PaymentDeclined;
use App\Checkout\Domain\PaymentGatewayUnreachable;
use App\Checkout\Domain\PaymentMethod;
use App\Checkout\UseCases\Inputs\CheckoutByCardInput;
use App\Foundation\Domain\Notification;
use App\Foundation\Domain\Notifier;
use App\Foundation\Domain\ReferenceGenerator;

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
