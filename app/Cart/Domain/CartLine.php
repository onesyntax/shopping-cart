<?php

declare(strict_types=1);

namespace App\Cart\Domain;

use App\Foundation\Domain\Money;
use App\Foundation\Domain\Quantity;

/**
 * A single line on a cart: one catalog item, the unit price captured when it
 * was added, and how many units the line holds.
 */
final class CartLine
{
    public function __construct(
        public readonly string $itemName,
        public readonly Money $unitPrice,
        public readonly Quantity $quantity,
    ) {}

    public function withAdditionalQuantity(Quantity $extra): self
    {
        return new self($this->itemName, $this->unitPrice, $this->quantity->plus($extra));
    }

    public function subtotal(): Money
    {
        return $this->unitPrice->multiply($this->quantity->value);
    }
}
