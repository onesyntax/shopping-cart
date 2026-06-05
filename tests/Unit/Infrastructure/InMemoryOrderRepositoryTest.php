<?php

declare(strict_types=1);

use App\Domain\Checkout\Order;
use App\Domain\Checkout\OrderLine;
use App\Domain\Checkout\OrderStatus;
use App\Domain\Checkout\PaymentMethod;
use App\Domain\Shared\Money;
use App\Domain\Shared\Quantity;
use App\Infrastructure\Persistence\InMemory\InMemoryOrderRepository;

function orderFor(string $ownerId, string $reference): Order
{
    return new Order(
        reference: $reference,
        ownerId: $ownerId,
        lines: [new OrderLine('Notebook', Money::ofCents(10000), Quantity::of(1))],
        paymentMethod: PaymentMethod::Card,
        status: OrderStatus::Paid,
    );
}

it('returns null when an owner has no orders', function () {
    expect((new InMemoryOrderRepository)->latestForOwner('alice'))->toBeNull();
});

it('returns the most recently saved order for an owner', function () {
    $repo = new InMemoryOrderRepository;
    $repo->save(orderFor('alice', 'ORD-1'));
    $repo->save(orderFor('alice', 'ORD-2'));

    expect($repo->latestForOwner('alice')->reference)->toBe('ORD-2');
});

it('keeps orders separate per owner', function () {
    $repo = new InMemoryOrderRepository;
    $repo->save(orderFor('alice', 'ORD-1'));
    $repo->save(orderFor('bob', 'ORD-2'));

    expect($repo->latestForOwner('alice')->reference)->toBe('ORD-1');
});
