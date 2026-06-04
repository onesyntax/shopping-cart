<?php

declare(strict_types=1);

use App\Application\Checkout\ViewOrderConfirmation;
use App\Domain\Checkout\Invoice;
use App\Domain\Checkout\Order;
use App\Domain\Checkout\OrderLine;
use App\Domain\Checkout\OrderStatus;
use App\Domain\Checkout\PaymentMethod;
use App\Domain\Shared\Money;
use App\Domain\Shared\Quantity;
use App\Infrastructure\Persistence\InMemory\InMemoryInvoiceRepository;
use App\Infrastructure\Persistence\InMemory\InMemoryOrderRepository;

function placedOrder(): Order
{
    return new Order(
        reference: 'ORD-1',
        ownerId: 'shopper-1',
        lines: [new OrderLine('Pen', Money::ofCents(5000), Quantity::of(2))],
        paymentMethod: PaymentMethod::Card,
        status: OrderStatus::Paid,
        paymentReference: 'PAY-1',
    );
}

it('returns null when the shopper has no order yet', function () {
    $useCase = new ViewOrderConfirmation(new InMemoryOrderRepository, new InMemoryInvoiceRepository);

    expect($useCase->handle('shopper-1'))->toBeNull();
});

it('returns the latest order together with its invoice', function () {
    $orders = new InMemoryOrderRepository;
    $orders->save(placedOrder());
    $invoices = new InMemoryInvoiceRepository;
    $invoices->save(new Invoice('INV-1', 'ORD-1', Money::ofCents(10000)));

    $confirmation = (new ViewOrderConfirmation($orders, $invoices))->handle('shopper-1');

    expect($confirmation->order->reference)->toBe('ORD-1')
        ->and($confirmation->invoice)->not->toBeNull()
        ->and($confirmation->invoice->reference)->toBe('INV-1');
});

it('returns the order with a null invoice when none was issued', function () {
    $orders = new InMemoryOrderRepository;
    $orders->save(placedOrder());

    $confirmation = (new ViewOrderConfirmation($orders, new InMemoryInvoiceRepository))->handle('shopper-1');

    expect($confirmation->order->reference)->toBe('ORD-1')
        ->and($confirmation->invoice)->toBeNull();
});
