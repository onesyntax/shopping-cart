<?php

declare(strict_types=1);

namespace App\Application\Cart;

final class RemoveItemFromCartInput
{
    public function __construct(
        public readonly string $ownerId,
        public readonly string $itemName,
    ) {}
}
