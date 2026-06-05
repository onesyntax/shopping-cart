<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Cart\ViewCart;
use App\Domain\Cart\CartRepository;
use App\Domain\Catalog\ItemRepository;
use App\Domain\Checkout\CardPaymentGateway;
use App\Domain\Checkout\InvoiceRepository;
use App\Domain\Checkout\OrderRepository;
use App\Domain\Notification\Notifier;
use App\Domain\Shared\ReferenceGenerator;
use App\Http\Support\CurrentShopper;
use App\Infrastructure\Notification\RecordingNotifier;
use App\Infrastructure\Payment\FakeCardPaymentGateway;
use App\Infrastructure\Persistence\Eloquent\EloquentCartRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentInvoiceRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentItemRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentOrderRepository;
use App\Infrastructure\Shared\DatabaseReferenceGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\Support\ServiceProvider;

/**
 * Wires the Domain/Application interfaces to their Infrastructure
 * implementations. This is the only place the inner layers are connected to
 * concrete adapters, honouring the Dependency Rule.
 *
 * The repositories are Eloquent-backed so the cart, catalog and orders persist
 * across HTTP requests. Bindings are singletons; the repositories are stateless,
 * so a shared instance is safe and every use case resolves the same adapter.
 */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ReferenceGenerator::class, DatabaseReferenceGenerator::class);

        $this->app->singleton(ItemRepository::class, EloquentItemRepository::class);
        $this->app->singleton(CartRepository::class, EloquentCartRepository::class);
        $this->app->singleton(OrderRepository::class, EloquentOrderRepository::class);
        $this->app->singleton(InvoiceRepository::class, EloquentInvoiceRepository::class);

        $this->app->singleton(Notifier::class, RecordingNotifier::class);
        $this->app->singleton(CardPaymentGateway::class, FakeCardPaymentGateway::class);
    }

    public function boot(): void
    {
        // The storefront layout shows a live cart count in its header. Compose
        // it from the same ViewCart use case the cart page uses, so the badge
        // and the page can never disagree. Guarded for console runs with no
        // HTTP session.
        ViewFacade::composer('layouts.app', function (View $view): void {
            $count = 0;

            if (request()->hasSession()) {
                $count = $this->app->make(ViewCart::class)
                    ->handle(CurrentShopper::id(request()))
                    ->lineCount();
            }

            $view->with('cartCount', $count);
        });
    }
}
