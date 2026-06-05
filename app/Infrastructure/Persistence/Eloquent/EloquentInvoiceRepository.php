<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Checkout\Invoice;
use App\Domain\Checkout\InvoiceRepository;
use App\Domain\Shared\Money;
use App\Infrastructure\Persistence\Eloquent\Models\InvoiceRecord;

/**
 * Eloquent-backed invoice store, keyed by the order it bills.
 */
final class EloquentInvoiceRepository implements InvoiceRepository
{
    public function save(Invoice $invoice): void
    {
        InvoiceRecord::query()->updateOrCreate(
            ['order_reference' => $invoice->orderReference],
            [
                'reference' => $invoice->reference,
                'total_cents' => $invoice->total->cents,
            ],
        );
    }

    public function forOrder(string $orderReference): ?Invoice
    {
        $record = InvoiceRecord::query()->where('order_reference', $orderReference)->first();

        return $record === null
            ? null
            : new Invoice($record->reference, $record->order_reference, Money::ofCents($record->total_cents));
    }
}
