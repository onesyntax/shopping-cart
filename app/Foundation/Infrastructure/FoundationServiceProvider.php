<?php

declare(strict_types=1);

namespace App\Foundation\Infrastructure;

use App\Foundation\Domain\Notifier;
use App\Foundation\Domain\ReferenceGenerator;
use Illuminate\Support\ServiceProvider;

/**
 * Composition root for the Foundation component: wires its cross-cutting
 * Domain interfaces (reference generation, notifications) to their concrete
 * Infrastructure adapters. Bindings are singletons; the adapters are stateless,
 * so a shared instance is safe across a request.
 */
class FoundationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ReferenceGenerator::class, DatabaseReferenceGenerator::class);
        $this->app->singleton(Notifier::class, RecordingNotifier::class);
    }
}
