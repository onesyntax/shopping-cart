<?php

declare(strict_types=1);

namespace App\Checkout\Infrastructure\Persistence;

use App\Checkout\Domain\Invoice;
use App\Checkout\Domain\InvoiceRepository;

final class InMemoryInvoiceRepository implements InvoiceRepository
{
    /** @var array<string, Invoice> */
    private array $invoices = [];

    public function save(Invoice $invoice): void
    {
        $this->invoices[$invoice->orderReference] = $invoice;
    }

    public function forOrder(string $orderReference): ?Invoice
    {
        return $this->invoices[$orderReference] ?? null;
    }
}
