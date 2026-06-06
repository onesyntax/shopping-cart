<?php

use App\Cart\Infrastructure\CartServiceProvider;
use App\Catalog\Infrastructure\CatalogServiceProvider;
use App\Checkout\Infrastructure\CheckoutServiceProvider;
use App\Foundation\Infrastructure\FoundationServiceProvider;

return [
    // Each component owns its composition root and registers its own bindings.
    FoundationServiceProvider::class,
    CatalogServiceProvider::class,
    CartServiceProvider::class,
    CheckoutServiceProvider::class,
];
