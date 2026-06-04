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
use App\Infrastructure\Persistence\Eloquent\EloquentCartRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentInvoiceRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentItemRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentOrderRepository;
use App\Infrastructure\Shared\DatabaseReferenceGenerator;

it('binds each domain interface to its infrastructure implementation', function (string $abstract, string $concrete) {
    expect(app($abstract))->toBeInstanceOf($concrete);
})->with([
    'reference generator' => [ReferenceGenerator::class, DatabaseReferenceGenerator::class],
    'item repository' => [ItemRepository::class, EloquentItemRepository::class],
    'cart repository' => [CartRepository::class, EloquentCartRepository::class],
    'order repository' => [OrderRepository::class, EloquentOrderRepository::class],
    'invoice repository' => [InvoiceRepository::class, EloquentInvoiceRepository::class],
    'notifier' => [Notifier::class, RecordingNotifier::class],
    'card payment gateway' => [CardPaymentGateway::class, FakeCardPaymentGateway::class],
]);

it('binds each interface as a singleton so state is shared within a request', function () {
    expect(app(CartRepository::class))->toBe(app(CartRepository::class));
});
