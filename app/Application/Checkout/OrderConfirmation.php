<?php

declare(strict_types=1);

namespace App\Application\Checkout;

use App\Domain\Checkout\Invoice;
use App\Domain\Checkout\Order;

/**
 * What a shopper sees after placing an order: the order itself and, when one was
 * issued, its invoice. Bank-deposit orders carry no invoice until the deposit
 * clears, so {@see self::$invoice} may be null.
 */
final class OrderConfirmation
{
    public function __construct(
        public readonly Order $order,
        public readonly ?Invoice $invoice,
    ) {}
}
