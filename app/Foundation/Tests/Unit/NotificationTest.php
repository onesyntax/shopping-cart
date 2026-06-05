<?php

declare(strict_types=1);

use App\Checkout\Domain\Order;
use App\Checkout\Domain\OrderLine;
use App\Checkout\Domain\OrderStatus;
use App\Checkout\Domain\PaymentMethod;
use App\Foundation\Domain\Money;
use App\Foundation\Domain\Notification;
use App\Foundation\Domain\NotificationKind;
use App\Foundation\Domain\Quantity;

function paidOrderWithNotebook(): Order
{
    return new Order(
        reference: 'ORD-1',
        ownerId: 'alice',
        lines: [new OrderLine('Notebook', Money::ofCents(10000), Quantity::of(2))],
        paymentMethod: PaymentMethod::Card,
        status: OrderStatus::Paid,
    );
}

it('carries the order lines so the shopper can be told what they bought', function () {
    $note = Notification::orderPaid(paidOrderWithNotebook());

    expect($note->kind)->toBe(NotificationKind::OrderPaid);
    expect($note->recipient)->toBe('alice');
    expect($note->quantityOf('Notebook'))->toBe(2);
});

it('reports zero quantity for an item not on the notification', function () {
    expect(Notification::orderPaid(paidOrderWithNotebook())->quantityOf('Pen'))->toBe(0);
});
