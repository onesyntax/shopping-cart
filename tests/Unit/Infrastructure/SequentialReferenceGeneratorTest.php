<?php

declare(strict_types=1);

use App\Infrastructure\Shared\SequentialReferenceGenerator;

it('numbers references sequentially from one within a prefix', function () {
    $gen = new SequentialReferenceGenerator();

    expect($gen->next('ORD'))->toBe('ORD-1');
    expect($gen->next('ORD'))->toBe('ORD-2');
    expect($gen->next('ORD'))->toBe('ORD-3');
});

it('keeps an independent counter per prefix', function () {
    $gen = new SequentialReferenceGenerator();

    expect($gen->next('ORD'))->toBe('ORD-1');
    expect($gen->next('INV'))->toBe('INV-1');
    expect($gen->next('ORD'))->toBe('ORD-2');
});
