<?php

declare(strict_types=1);

use App\Application\Cart\AddItemToCart;
use App\Application\Cart\AddItemToCartInput;
use App\Application\Checkout\CheckoutByCard;
use App\Application\Checkout\CheckoutByCardInput;
use App\Domain\Notification\NotificationKind;
use App\Infrastructure\Notification\RecordingNotifier;
use App\Infrastructure\Payment\FakeCardPaymentGateway;
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
    $references = new SequentialReferenceGenerator;
    $this->gateway = new FakeCardPaymentGateway($references);

    $this->addToCart = new AddItemToCart($this->items, $this->carts);
    $this->checkout = new CheckoutByCard(
        $this->carts,
        $this->orders,
        $this->invoices,
        $this->gateway,
        $this->notifier,
        $references,
    );

    $this->seed = fn (string $item, int $qty) => $this->addToCart->handle(
        new AddItemToCartInput('alice', $item, $qty),
    );
});

it('rejects checking out an empty cart', function () {
    $this->checkout->handle(new CheckoutByCardInput('alice', FakeCardPaymentGateway::VALID));
})->throws('The cart is empty.');

it('places a paid order, issues an invoice, empties the cart and notifies', function () {
    ($this->seed)('Hardcover notebook', 2);

    $order = $this->checkout->handle(new CheckoutByCardInput('alice', FakeCardPaymentGateway::VALID));

    expect($order->isPaid())->toBeTrue();
    expect($order->paymentMethod->label())->toBe('card');
    expect($order->quantityOf('Hardcover notebook'))->toBe(2);
    expect($order->lineCount())->toBe(1);
    expect($order->total()->format())->toBe('$200.00');
    expect($order->paymentReference)->not->toBeNull();

    expect($this->invoices->forOrder($order->reference)->total->format())->toBe('$200.00');
    expect($this->carts->forOwner('alice')->isEmpty())->toBeTrue();

    $note = $this->notifier->lastFor('alice');
    expect($note->kind)->toBe(NotificationKind::OrderPaid);
    expect($note->quantityOf('Hardcover notebook'))->toBe(2);
    expect($note->orderReference)->toBe($order->reference);
});

it('records every line of a multi-line cart', function () {
    ($this->seed)('Hardcover notebook', 2);
    ($this->seed)('Ballpoint pen', 1);

    $order = $this->checkout->handle(new CheckoutByCardInput('alice', FakeCardPaymentGateway::VALID));

    expect($order->lineCount())->toBe(2);
    expect($order->total()->format())->toBe('$250.00');
    expect($this->invoices->forOrder($order->reference)->total->format())->toBe('$250.00');
});

it('places no order and leaves the cart untouched when the card is declined', function () {
    ($this->seed)('Hardcover notebook', 2);

    expect(fn () => $this->checkout->handle(
        new CheckoutByCardInput('alice', FakeCardPaymentGateway::DECLINED),
    ))->toThrow('The card was declined.');

    expect($this->orders->latestForOwner('alice'))->toBeNull();
    expect($this->carts->forOwner('alice')->quantityOf('Hardcover notebook'))->toBe(2);
    expect($this->carts->forOwner('alice')->lineCount())->toBe(1);

    $note = $this->notifier->lastFor('alice');
    expect($note->kind)->toBe(NotificationKind::PaymentFailed);
    expect($note->reason)->toContain('card was declined');
});

it('places no order and leaves the cart untouched when the gateway is unreachable', function () {
    ($this->seed)('Hardcover notebook', 2);

    expect(fn () => $this->checkout->handle(
        new CheckoutByCardInput('alice', FakeCardPaymentGateway::UNREACHABLE),
    ))->toThrow('The bank could not be reached.');

    expect($this->orders->latestForOwner('alice'))->toBeNull();
    expect($this->carts->forOwner('alice')->quantityOf('Hardcover notebook'))->toBe(2);

    $note = $this->notifier->lastFor('alice');
    expect($note->kind)->toBe(NotificationKind::PaymentFailed);
    expect($note->reason)->toContain('bank could not be reached');
});

it('keeps the prices that were in effect when the order was placed', function () {
    ($this->seed)('Hardcover notebook', 1);

    $order = $this->checkout->handle(new CheckoutByCardInput('alice', FakeCardPaymentGateway::VALID));

    // Catalog price later changes; the order must not.
    $this->items->save(catalogItem('Hardcover notebook', 'A4 hardcover notebook with 200 ruled pages', 15000));

    expect($order->total()->format())->toBe('$100.00');
    expect($order->lineFor('Hardcover notebook')->unitPrice->format())->toBe('$100.00');
});

it('does not affect another shopper\'s cart', function () {
    ($this->seed)('Hardcover notebook', 2);
    $this->addToCart->handle(new AddItemToCartInput('bob', 'Ballpoint pen', 1));

    $this->checkout->handle(new CheckoutByCardInput('alice', FakeCardPaymentGateway::VALID));

    expect($this->carts->forOwner('alice')->isEmpty())->toBeTrue();
    expect($this->carts->forOwner('bob')->quantityOf('Ballpoint pen'))->toBe(1);
    expect($this->carts->forOwner('bob')->lineCount())->toBe(1);
});
