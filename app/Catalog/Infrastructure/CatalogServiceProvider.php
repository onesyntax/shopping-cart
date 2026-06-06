<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure;

use App\Catalog\Domain\ItemRepository;
use App\Catalog\Infrastructure\Persistence\EloquentItemRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Composition root for the Catalog component: binds the catalog repository
 * interface to its Eloquent-backed implementation as a singleton.
 */
class CatalogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ItemRepository::class, EloquentItemRepository::class);
    }
}
