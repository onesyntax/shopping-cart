<?php

declare(strict_types=1);

namespace App\Application\Checkout;

use App\Domain\Checkout\Order;
use App\Domain\Checkout\OrderStatus;
use App\Domain\Checkout\PaymentMethod;
use App\Domain\Notification\Notification;

/**
 * Checks out a cart against a bank deposit the shopper has already made. The
 * order records the deposit reference and date and is left awaiting
 * confirmation; no invoice is issued until the deposit is verified. The cart is
 * emptied.
 */
final class CheckoutByBankDeposit extends Checkout
{
    public function handle(CheckoutByBankDepositInput $input): Order
    {
        $cart = $this->cartFor($input->ownerId);

        $order = new Order(
            reference: $this->newOrderReference(),
            ownerId: $input->ownerId,
            lines: $this->snapshotLines($cart),
            paymentMethod: PaymentMethod::BankDeposit,
            status: OrderStatus::AwaitingDepositConfirmation,
            depositReference: $input->depositReference,
            depositDate: $input->depositDate,
        );

        return $this->place($cart, $order, null, Notification::awaitingDepositConfirmation(...));
    }
}
