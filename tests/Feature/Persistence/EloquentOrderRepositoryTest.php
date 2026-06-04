<?php

declare(strict_types=1);

use App\Domain\Checkout\Order;
use App\Domain\Checkout\OrderLine;
use App\Domain\Checkout\OrderStatus;
use App\Domain\Checkout\PaymentMethod;
use App\Domain\Shared\Money;
use App\Domain\Shared\Quantity;
use App\Infrastructure\Persistence\Eloquent\EloquentOrderRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->repository = new EloquentOrderRepository;
});

function paidOrder(string $reference, string $ownerId): Order
{
    return new Order(
        reference: $reference,
        ownerId: $ownerId,
        lines: [
            new OrderLine('Notebook', Money::ofCents(10000), Quantity::of(2)),
            new OrderLine('Pen', Money::ofCents(5000), Quantity::of(1)),
        ],
        paymentMethod: PaymentMethod::Card,
        status: OrderStatus::Paid,
        paymentReference: 'PAY-1',
    );
}

it('returns null when an owner has no orders', function () {
    expect($this->repository->latestForOwner('shopper-1'))->toBeNull();
});

it('saves an order and reloads it whole', function () {
    $this->repository->save(paidOrder('ORD-1', 'shopper-1'));

    $order = $this->repository->latestForOwner('shopper-1');

    expect($order->reference)->toBe('ORD-1')
        ->and($order->paymentMethod)->toBe(PaymentMethod::Card)
        ->and($order->status)->toBe(OrderStatus::Paid)
        ->and($order->paymentReference)->toBe('PAY-1')
        ->and($order->lineCount())->toBe(2)
        ->and($order->quantityOf('Notebook'))->toBe(2)
        ->and($order->total())->toBeMoney('$250.00');
});

it('returns the most recently saved order for an owner', function () {
    $this->repository->save(paidOrder('ORD-1', 'shopper-1'));
    $this->repository->save(new Order(
        reference: 'ORD-2',
        ownerId: 'shopper-1',
        lines: [new OrderLine('Pen', Money::ofCents(5000), Quantity::of(1))],
        paymentMethod: PaymentMethod::BankDeposit,
        status: OrderStatus::AwaitingDepositConfirmation,
        depositReference: 'DEP-9',
        depositDate: '2026-06-04',
    ));

    $order = $this->repository->latestForOwner('shopper-1');

    expect($order->reference)->toBe('ORD-2')
        ->and($order->paymentMethod)->toBe(PaymentMethod::BankDeposit)
        ->and($order->depositReference)->toBe('DEP-9')
        ->and($order->depositDate)->toBe('2026-06-04');
});
