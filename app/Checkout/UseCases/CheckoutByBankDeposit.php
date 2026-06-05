<?php

declare(strict_types=1);

namespace App\Checkout\UseCases;

use App\Checkout\Domain\Order;
use App\Checkout\Domain\OrderStatus;
use App\Checkout\Domain\PaymentMethod;
use App\Checkout\UseCases\Inputs\CheckoutByBankDepositInput;
use App\Foundation\Domain\Notification;

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
