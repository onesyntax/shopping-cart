<?php

declare(strict_types=1);

namespace App\Domain\Cart;

use App\Domain\Catalog\Item;
use App\Domain\Shared\Money;
use App\Domain\Shared\Quantity;

/**
 * A shopper's cart. Lines are keyed by item name so adding the same item again
 * merges into the existing line rather than creating a duplicate.
 */
final class Cart
{
    /** @var array<string, CartLine> */
    private array $lines = [];

    public function __construct(public readonly string $ownerId) {}

    public function addItem(Item $item, Quantity $quantity): void
    {
        $existing = $this->lines[$item->name] ?? null;

        $this->lines[$item->name] = $existing !== null
            ? $existing->withAdditionalQuantity($quantity)
            : new CartLine($item->name, $item->price, $quantity);
    }

    public function removeItem(string $itemName): void
    {
        if (! isset($this->lines[$itemName])) {
            throw ItemNotInCart::named($itemName);
        }

        unset($this->lines[$itemName]);
    }

    public function isEmpty(): bool
    {
        return $this->lines === [];
    }

    /** @return list<CartLine> */
    public function lines(): array
    {
        return array_values($this->lines);
    }

    public function lineCount(): int
    {
        return count($this->lines);
    }

    public function quantityOf(string $itemName): int
    {
        return isset($this->lines[$itemName])
            ? $this->lines[$itemName]->quantity->value
            : 0;
    }

    public function total(): Money
    {
        $total = Money::zero();
        foreach ($this->lines as $line) {
            $total = $total->add($line->subtotal());
        }

        return $total;
    }

    public function clear(): void
    {
        $this->lines = [];
    }
}
