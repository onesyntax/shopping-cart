<?php

declare(strict_types=1);

namespace App\Application\Checkout;

use App\Domain\Checkout\InvoiceRepository;
use App\Domain\Checkout\OrderRepository;

/**
 * Loads a shopper's most recent order and any invoice issued for it, for the
 * post-checkout confirmation screen. Returns null when they have no order yet.
 */
final class ViewOrderConfirmation
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly InvoiceRepository $invoices,
    ) {}

    public function handle(string $ownerId): ?OrderConfirmation
    {
        $order = $this->orders->latestForOwner($ownerId);

        if ($order === null) {
            return null;
        }

        return new OrderConfirmation($order, $this->invoices->forOrder($order->reference));
    }
}
