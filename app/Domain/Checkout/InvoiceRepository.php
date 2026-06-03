<?php

declare(strict_types=1);

namespace App\Domain\Checkout;

interface InvoiceRepository
{
    public function save(Invoice $invoice): void;

    public function forOrder(string $orderReference): ?Invoice;
}
