<?php

declare(strict_types=1);

use App\Domain\Checkout\Invoice;
use App\Domain\Shared\Money;
use App\Infrastructure\Persistence\Eloquent\EloquentInvoiceRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->repository = new EloquentInvoiceRepository;
});

it('returns null when no invoice exists for an order', function () {
    expect($this->repository->forOrder('ORD-1'))->toBeNull();
});

it('saves an invoice and reloads it by order reference', function () {
    $this->repository->save(new Invoice('INV-1', 'ORD-1', Money::ofCents(25000)));

    $invoice = $this->repository->forOrder('ORD-1');

    expect($invoice)->toBeInstanceOf(Invoice::class)
        ->and($invoice->reference)->toBe('INV-1')
        ->and($invoice->orderReference)->toBe('ORD-1')
        ->and($invoice->total)->toBeMoney('$250.00');
});
