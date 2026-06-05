<?php

declare(strict_types=1);

use App\Cart\Domain\CartRepository;
use App\Cart\Infrastructure\Persistence\EloquentCartRepository;
use App\Catalog\Domain\ItemRepository;
use App\Catalog\Infrastructure\Persistence\EloquentItemRepository;
use App\Checkout\Domain\CardPaymentGateway;
use App\Checkout\Domain\InvoiceRepository;
use App\Checkout\Domain\OrderRepository;
use App\Checkout\Infrastructure\Payment\FakeCardPaymentGateway;
use App\Checkout\Infrastructure\Persistence\EloquentInvoiceRepository;
use App\Checkout\Infrastructure\Persistence\EloquentOrderRepository;
use App\Foundation\Domain\Notifier;
use App\Foundation\Domain\ReferenceGenerator;
use App\Foundation\Infrastructure\DatabaseReferenceGenerator;
use App\Foundation\Infrastructure\RecordingNotifier;

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
