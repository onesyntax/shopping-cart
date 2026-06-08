<?php

declare(strict_types=1);

use App\Domain\Checkout\Order;
use App\Domain\Checkout\OrderLine;
use App\Domain\Checkout\OrderStatus;
use App\Domain\Checkout\PaymentMethod;
use App\Domain\Shared\Money;
use App\Domain\Shared\Quantity;

function orderWithNotebook(): Order
{
    return new Order(
        reference: 'ORD-1',
        ownerId: 'alice',
        lines: [new OrderLine('Notebook', Money::ofCents(10000), Quantity::of(2))],
        paymentMethod: PaymentMethod::Card,
        status: OrderStatus::Paid,
    );
}

it('reports the quantity of a line it holds', function () {
    expect(orderWithNotebook()->quantityOf('Notebook'))->toBe(2);
});

it('reports zero for an item it does not hold', function () {
    expect(orderWithNotebook()->quantityOf('Pen'))->toBe(0);
});
