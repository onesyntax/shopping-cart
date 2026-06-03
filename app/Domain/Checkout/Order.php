<?php

declare(strict_types=1);

namespace App\Domain\Checkout;

use App\Domain\Shared\Money;

/**
 * A placed order: an immutable snapshot of the cart's lines, the chosen payment
 * method, and the resulting status. Optional fields capture payment-method
 * specifics (a card transaction reference, or a bank-deposit reference + date).
 */
final class Order
{
    /**
     * @param  list<OrderLine>  $lines
     */
    public function __construct(
        public readonly string $reference,
        public readonly string $ownerId,
        public readonly array $lines,
        public readonly PaymentMethod $paymentMethod,
        public readonly OrderStatus $status,
        public readonly ?string $paymentReference = null,
        public readonly ?string $depositReference = null,
        public readonly ?string $depositDate = null,
    ) {}

    public function total(): Money
    {
        $total = Money::zero();
        foreach ($this->lines as $line) {
            $total = $total->add($line->subtotal());
        }

        return $total;
    }

    public function lineCount(): int
    {
        return count($this->lines);
    }

    public function quantityOf(string $itemName): int
    {
        return $this->lineFor($itemName)?->quantity->value ?? 0;
    }

    public function lineFor(string $itemName): ?OrderLine
    {
        foreach ($this->lines as $line) {
            if ($line->itemName === $itemName) {
                return $line;
            }
        }

        return null;
    }

    public function isPaid(): bool
    {
        return $this->status === OrderStatus::Paid;
    }
}
