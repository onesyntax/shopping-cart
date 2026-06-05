<?php

declare(strict_types=1);

namespace App\Checkout\Domain;

use App\Cart\Domain\CartLine;
use App\Foundation\Domain\Money;
use App\Foundation\Domain\Quantity;

/**
 * A line on a placed order. It snapshots the item name, the unit price in
 * effect at checkout, and the quantity, so later catalog changes never alter
 * a recorded order.
 */
final class OrderLine
{
    public function __construct(
        public readonly string $itemName,
        public readonly Money $unitPrice,
        public readonly Quantity $quantity,
    ) {}

    public static function fromCartLine(CartLine $line): self
    {
        return new self($line->itemName, $line->unitPrice, $line->quantity);
    }

    /**
     * @param  iterable<CartLine>  $cartLines
     * @return list<self>
     */
    public static function fromCartLines(iterable $cartLines): array
    {
        $lines = [];
        foreach ($cartLines as $cartLine) {
            $lines[] = self::fromCartLine($cartLine);
        }

        return $lines;
    }

    public function subtotal(): Money
    {
        return $this->unitPrice->multiply($this->quantity->value);
    }
}
