<?php

declare(strict_types=1);

use App\Application\Cart\AddItemToCart;
use App\Application\Cart\AddItemToCartInput;
use App\Application\Checkout\CheckoutByBankDeposit;
use App\Application\Checkout\CheckoutByBankDepositInput;
use App\Application\Checkout\CheckoutByCashOnDelivery;
use App\Application\Checkout\CheckoutByCashOnHand;
use App\Application\Checkout\CheckoutInput;
use App\Domain\Checkout\OrderStatus;
use App\Domain\Notification\NotificationKind;
use App\Infrastructure\Notification\RecordingNotifier;
use App\Infrastructure\Persistence\InMemory\InMemoryCartRepository;
use App\Infrastructure\Persistence\InMemory\InMemoryInvoiceRepository;
use App\Infrastructure\Persistence\InMemory\InMemoryOrderRepository;
use App\Infrastructure\Shared\SequentialReferenceGenerator;

beforeEach(function () {
    $this->items = standardCatalog();
    $this->carts = new InMemoryCartRepository;
    $this->orders = new InMemoryOrderRepository;
    $this->invoices = new InMemoryInvoiceRepository;
    $this->notifier = new RecordingNotifier;
    $this->references = new SequentialReferenceGenerator;

    $this->addToCart = new AddItemToCart($this->items, $this->carts);
    $this->seed = fn (string $item, int $qty) => $this->addToCart->handle(
        new AddItemToCartInput('alice', $item, $qty),
    );
});

describe('bank deposit', function () {
    beforeEach(function () {
        $this->checkout = new CheckoutByBankDeposit($this->carts, $this->orders, $this->notifier, $this->references);
    });

    it('rejects an empty cart', function () {
        $this->checkout->handle(new CheckoutByBankDepositInput('alice', 'BD-48217', '2026-06-01'));
    })->throws('The cart is empty.');

    it('places an order awaiting confirmation, records the deposit, issues no invoice', function () {
        ($this->seed)('Hardcover notebook', 2);

        $order = $this->checkout->handle(new CheckoutByBankDepositInput('alice', 'BD-48217', '2026-06-01'));

        expect($order->status)->toBe(OrderStatus::AwaitingDepositConfirmation);
        expect($order->paymentMethod->label())->toBe('bank deposit');
        expect($order->depositReference)->toBe('BD-48217');
        expect($order->depositDate)->toBe('2026-06-01');
        expect($order->total()->format())->toBe('$200.00');
        expect($this->invoices->forOrder($order->reference))->toBeNull();
        expect($this->carts->forOwner('alice')->isEmpty())->toBeTrue();

        $note = $this->notifier->lastFor('alice');
        expect($note->kind)->toBe(NotificationKind::AwaitingDepositConfirmation);
        expect($note->orderReference)->toBe($order->reference);
    });
});

describe('cash on delivery', function () {
    beforeEach(function () {
        $this->checkout = new CheckoutByCashOnDelivery(
            $this->carts, $this->orders, $this->invoices, $this->notifier, $this->references,
        );
    });

    it('rejects an empty cart', function () {
        $this->checkout->handle(new CheckoutInput('alice'));
    })->throws('The cart is empty.');

    it('places an order awaiting payment on delivery and issues an invoice', function () {
        ($this->seed)('Hardcover notebook', 2);

        $order = $this->checkout->handle(new CheckoutInput('alice'));

        expect($order->status)->toBe(OrderStatus::AwaitingPaymentOnDelivery);
        expect($order->paymentMethod->label())->toBe('cash on delivery');
        expect($order->total()->format())->toBe('$200.00');
        expect($this->invoices->forOrder($order->reference)->total->format())->toBe('$200.00');
        expect($this->carts->forOwner('alice')->isEmpty())->toBeTrue();
        expect($this->notifier->lastFor('alice')->kind)->toBe(NotificationKind::AwaitingPaymentOnDelivery);
    });
});

describe('cash on hand', function () {
    beforeEach(function () {
        $this->checkout = new CheckoutByCashOnHand(
            $this->carts, $this->orders, $this->invoices, $this->notifier, $this->references,
        );
    });

    it('rejects an empty cart', function () {
        $this->checkout->handle(new CheckoutInput('alice'));
    })->throws('The cart is empty.');

    it('settles immediately: paid order, invoice, empty cart', function () {
        ($this->seed)('Hardcover notebook', 2);
        ($this->seed)('Ballpoint pen', 1);

        $order = $this->checkout->handle(new CheckoutInput('alice'));

        expect($order->isPaid())->toBeTrue();
        expect($order->paymentMethod->label())->toBe('cash on hand');
        expect($order->lineCount())->toBe(2);
        expect($order->total()->format())->toBe('$250.00');
        expect($this->invoices->forOrder($order->reference)->total->format())->toBe('$250.00');
        expect($this->carts->forOwner('alice')->isEmpty())->toBeTrue();
        expect($this->notifier->lastFor('alice')->kind)->toBe(NotificationKind::OrderPaid);
    });
});
