<?php

declare(strict_types=1);

use App\Catalog\Infrastructure\Persistence\InMemoryItemRepository;
use App\Catalog\UseCases\ListCatalog;

it('returns every catalog item', function () {
    $useCase = new ListCatalog(standardCatalog());

    $items = $useCase->handle();

    expect($items)->toHaveCount(2)
        ->and(array_map(fn ($item) => $item->name, $items))
        ->toBe(['Hardcover notebook', 'Ballpoint pen']);
});

it('returns an empty list when the catalog is empty', function () {
    $useCase = new ListCatalog(new InMemoryItemRepository);

    expect($useCase->handle())->toBe([]);
});
