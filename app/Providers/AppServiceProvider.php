<?php

declare(strict_types=1);

namespace App\Providers;

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
use Illuminate\Support\ServiceProvider;

/**
 * Wires the Domain/Application interfaces to their Infrastructure
 * implementations. This is the only place the inner layers are connected to
 * concrete adapters, honouring the Dependency Rule.
 *
 * Bindings are singletons so that, within a single request or test, every use
 * case shares the same in-memory state.
 */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ReferenceGenerator::class, SequentialReferenceGenerator::class);

        $this->app->singleton(ItemRepository::class, InMemoryItemRepository::class);
        $this->app->singleton(CartRepository::class, InMemoryCartRepository::class);
        $this->app->singleton(OrderRepository::class, InMemoryOrderRepository::class);
        $this->app->singleton(InvoiceRepository::class, InMemoryInvoiceRepository::class);

        $this->app->singleton(Notifier::class, RecordingNotifier::class);
        $this->app->singleton(CardPaymentGateway::class, FakeCardPaymentGateway::class);
    }

    public function boot(): void
    {
        //
    }
}
