<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\InMemory\InMemoryItemRepository;

it('returns no items when the catalog is empty', function () {
    expect((new InMemoryItemRepository)->all())->toBe([]);
});

it('lists every saved item in insertion order', function () {
    $repository = new InMemoryItemRepository;
    $notebook = catalogItem('Hardcover notebook', 'A4 hardcover notebook', 10000);
    $pen = catalogItem('Ballpoint pen', 'Blue ink, pack of 5', 5000);

    $repository->save($notebook);
    $repository->save($pen);

    expect($repository->all())->toBe([$notebook, $pen]);
});

it('overwrites an item with the same name rather than listing it twice', function () {
    $repository = new InMemoryItemRepository;
    $repository->save(catalogItem('Ballpoint pen', 'Old description', 5000));
    $repository->save(catalogItem('Ballpoint pen', 'New description', 6000));

    $all = $repository->all();

    expect($all)->toHaveCount(1)
        ->and($all[0]->description)->toBe('New description');
});
