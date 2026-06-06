<?php

declare(strict_types=1);

namespace App\Checkout\UseCases;

use App\Cart\Domain\CartRepository;
use App\Checkout\Domain\InvoiceRepository;
use App\Checkout\Domain\OrderRepository;
use App\Foundation\Domain\Notifier;
use App\Foundation\Domain\ReferenceGenerator;

/**
 * Base for checkout flows that issue an invoice as part of placing the order
 * (card, cash on delivery, cash on hand). It holds the invoice repository so the
 * concrete use cases share one constructor instead of repeating it; bank
 * deposit, which invoices only once the deposit clears, extends {@see Checkout}
 * directly.
 */
abstract class InvoicedCheckout extends Checkout
{
    public function __construct(
        CartRepository $carts,
        OrderRepository $orders,
        protected readonly InvoiceRepository $invoices,
        Notifier $notifier,
        ReferenceGenerator $references,
    ) {
        parent::__construct($carts, $orders, $notifier, $references);
    }
}
