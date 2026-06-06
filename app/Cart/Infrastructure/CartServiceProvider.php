<?php

declare(strict_types=1);

namespace App\Cart\Infrastructure;

use App\Cart\Domain\CartRepository;
use App\Cart\Infrastructure\Persistence\EloquentCartRepository;
use App\Cart\UseCases\ViewCart;
use App\Foundation\Infrastructure\Http\CurrentShopper;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\Support\ServiceProvider;

/**
 * Composition root for the Cart component: binds the cart repository to its
 * Eloquent-backed implementation, and composes the live cart-count badge shown
 * in the storefront layout.
 */
class CartServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CartRepository::class, EloquentCartRepository::class);
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
