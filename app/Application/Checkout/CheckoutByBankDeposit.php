<?php

declare(strict_types=1);

namespace App\Application\Checkout;

use App\Domain\Cart\CartRepository;
use App\Domain\Checkout\Order;
use App\Domain\Checkout\OrderRepository;
use App\Domain\Checkout\OrderStatus;
use App\Domain\Checkout\PaymentMethod;
use App\Domain\Notification\Notification;
use App\Domain\Notification\Notifier;
use App\Domain\Shared\ReferenceGenerator;

/**
 * Checks out a cart against a bank deposit the shopper has already made. The
 * order records the deposit reference and date and is left awaiting
 * confirmation; no invoice is issued until the deposit is verified. The cart is
 * emptied.
 */
final class CheckoutByBankDeposit
{
    use IssuesOrders;

    public function __construct(
        private readonly CartRepository $carts,
        private readonly OrderRepository $orders,
        private readonly Notifier $notifier,
        private readonly ReferenceGenerator $references,
    ) {}

    public function handle(CheckoutByBankDepositInput $input): Order
    {
        $cart = $this->carts->forOwner($input->ownerId);
        $this->guardNotEmpty($cart);

        $order = new Order(
            reference: $this->newOrderReference($this->references),
            ownerId: $input->ownerId,
            lines: $this->snapshotLines($cart),
            paymentMethod: PaymentMethod::BankDeposit,
            status: OrderStatus::AwaitingDepositConfirmation,
            depositReference: $input->depositReference,
            depositDate: $input->depositDate,
        );

        $this->orders->save($order);
        $this->emptyCart($this->carts, $cart);
        $this->notifier->notify(Notification::awaitingDepositConfirmation($order));

        return $order;
    }
}
