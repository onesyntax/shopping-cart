<?php

declare(strict_types=1);

namespace App\Domain\Shared;

/**
 * A whole number of units, at least 1. Rejects zero, negative and fractional
 * amounts with distinct messages so callers can tell the shopper exactly what
 * went wrong.
 */
final class Quantity
{
    private function __construct(public readonly int $value) {}

    public static function of(int $value): self
    {
        if ($value < 1) {
            throw new DomainException('The quantity must be at least 1.');
        }

        return new self($value);
    }

    /**
     * Build a quantity from raw user input (int, float or numeric string),
     * enforcing whole-number then minimum-of-one rules in that order.
     */
    public static function from(int|float|string $raw): self
    {
        if (is_string($raw)) {
            $trimmed = trim($raw);
            if (! preg_match('/^-?\d+(\.\d+)?$/', $trimmed)) {
                throw new DomainException('The quantity must be a whole number.');
            }
            $raw = str_contains($trimmed, '.') ? (float) $trimmed : (int) $trimmed;
        }

        if (is_float($raw)) {
            // Loose compare: a whole-valued float equals its integer truncation
            // (2.0 == 2), a fractional one does not (2.5 != 2).
            if ((int) $raw != $raw) {
                throw new DomainException('The quantity must be a whole number.');
            }
            $raw = (int) $raw;
        }

        return self::of($raw);
    }

    public function plus(Quantity $other): self
    {
        return new self($this->value + $other->value);
    }
}
