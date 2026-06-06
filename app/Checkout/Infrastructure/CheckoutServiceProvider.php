<?php

declare(strict_types=1);

namespace App\Checkout\Infrastructure;

use App\Checkout\Domain\CardPaymentGateway;
use App\Checkout\Domain\InvoiceRepository;
use App\Checkout\Domain\OrderRepository;
use App\Checkout\Infrastructure\Payment\FakeCardPaymentGateway;
use App\Checkout\Infrastructure\Persistence\EloquentInvoiceRepository;
use App\Checkout\Infrastructure\Persistence\EloquentOrderRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Composition root for the Checkout component: wires the order and invoice
 * repositories and the card payment gateway to their concrete adapters.
 * Bindings are singletons; the adapters are stateless.
 */
class CheckoutServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OrderRepository::class, EloquentOrderRepository::class);
        $this->app->singleton(InvoiceRepository::class, EloquentInvoiceRepository::class);
        $this->app->singleton(CardPaymentGateway::class, FakeCardPaymentGateway::class);
    }
}
