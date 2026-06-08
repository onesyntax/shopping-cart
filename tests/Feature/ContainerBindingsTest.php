<?php

declare(strict_types=1);

use App\Domain\Cart\CartRepository;
use App\Domain\Catalog\ItemRepository;
use App\Domain\Checkout\CardPaymentGateway;
use App\Domain\Checkout\InvoiceRepository;
use App\Domain\Checkout\OrderRepository;
use App\Domain\Notification\Notifier;
use App\Domain\Shared\ReferenceGenerator;
use App\Infrastructure\Notification\RecordingNotifier;
use App\Infrastructure\Payment\FakeCardPaymentGateway;
use App\Infrastructure\Persistence\InMemory\InMemoryCartRepository;
use App\Infrastructure\Persistence\InMemory\InMemoryInvoiceRepository;
use App\Infrastructure\Persistence\InMemory\InMemoryItemRepository;
use App\Infrastructure\Persistence\InMemory\InMemoryOrderRepository;
use App\Infrastructure\Shared\SequentialReferenceGenerator;

it('binds each domain interface to its infrastructure implementation', function (string $abstract, string $concrete) {
    expect(app($abstract))->toBeInstanceOf($concrete);
})->with([
    'reference generator' => [ReferenceGenerator::class, SequentialReferenceGenerator::class],
    'item repository' => [ItemRepository::class, InMemoryItemRepository::class],
    'cart repository' => [CartRepository::class, InMemoryCartRepository::class],
    'order repository' => [OrderRepository::class, InMemoryOrderRepository::class],
    'invoice repository' => [InvoiceRepository::class, InMemoryInvoiceRepository::class],
    'notifier' => [Notifier::class, RecordingNotifier::class],
    'card payment gateway' => [CardPaymentGateway::class, FakeCardPaymentGateway::class],
]);

it('binds each interface as a singleton so state is shared within a request', function () {
    expect(app(CartRepository::class))->toBe(app(CartRepository::class));
});
