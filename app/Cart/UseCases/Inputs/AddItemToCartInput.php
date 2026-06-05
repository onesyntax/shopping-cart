<?php

declare(strict_types=1);

namespace App\Cart\UseCases\Inputs;

final class AddItemToCartInput
{
    public function __construct(
        public readonly string $ownerId,
        public readonly string $itemName,
        public readonly int|float|string $quantity,
    ) {}
}
