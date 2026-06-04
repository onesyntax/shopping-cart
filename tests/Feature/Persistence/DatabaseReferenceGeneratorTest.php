<?php

declare(strict_types=1);

use App\Infrastructure\Shared\DatabaseReferenceGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('increments a per-prefix counter that survives across instances', function () {
    // Separate instances stand in for separate HTTP requests: the counter must
    // live in the database, not in process memory.
    expect((new DatabaseReferenceGenerator)->next('ORD'))->toBe('ORD-1')
        ->and((new DatabaseReferenceGenerator)->next('ORD'))->toBe('ORD-2')
        ->and((new DatabaseReferenceGenerator)->next('ORD'))->toBe('ORD-3');
});

it('keeps separate counters per prefix', function () {
    $generator = new DatabaseReferenceGenerator;

    expect($generator->next('ORD'))->toBe('ORD-1')
        ->and($generator->next('INV'))->toBe('INV-1')
        ->and($generator->next('ORD'))->toBe('ORD-2')
        ->and($generator->next('PAY'))->toBe('PAY-1');
});
