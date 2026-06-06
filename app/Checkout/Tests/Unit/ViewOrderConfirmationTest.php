<?php

declare(strict_types=1);

use App\Checkout\Domain\Invoice;
use App\Checkout\Domain\Order;
use App\Checkout\Domain\OrderLine;
use App\Checkout\Domain\OrderStatus;
use App\Checkout\Domain\PaymentMethod;
use App\Checkout\Infrastructure\Persistence\InMemoryInvoiceRepository;
use App\Checkout\Infrastructure\Persistence\InMemoryOrderRepository;
use App\Checkout\UseCases\ViewOrderConfirmation;
use App\Foundation\Domain\Money;
use App\Foundation\Domain\Quantity;

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
