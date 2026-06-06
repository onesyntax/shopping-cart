<?php

declare(strict_types=1);

namespace App\Domain\Shared;

/**
 * Money as an integer number of cents. Never a float. All arithmetic stays in
 * cents so we never accumulate rounding error.
 */
final class Money
{
    private function __construct(public readonly int $cents) {}

    public static function ofCents(int $cents): self
    {
        return new self($cents);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    /**
     * Parse a human string such as "$100.00", "-$10.00" or "$0.00".
     */
    public static function fromString(string $value): self
    {
        $text = trim($value);
        $negative = str_starts_with($text, '-');
        $text = ltrim($text, '-');
        $text = ltrim($text, '$');
        $text = trim($text);

        if (! preg_match('/^\d+(\.\d{1,2})?$/', $text)) {
            throw new DomainException("\"{$value}\" is not a valid money amount.");
        }

        [$whole, $fraction] = array_pad(explode('.', $text), 2, '0');
        $fraction = str_pad(substr($fraction, 0, 2), 2, '0');
        $cents = ((int) $whole) * 100 + (int) $fraction;

        return new self($negative ? -$cents : $cents);
    }

    public function add(Money $other): self
    {
        return new self($this->cents + $other->cents);
    }

    public function multiply(int $factor): self
    {
        return new self($this->cents * $factor);
    }

    public function isPositive(): bool
    {
        return $this->cents > 0;
    }

    public function equals(Money $other): bool
    {
        return $this->cents === $other->cents;
    }

    /**
     * Format as a "$1,234.56" string. Negative amounts get a leading minus.
     */
    public function format(): string
    {
        $sign = $this->cents < 0 ? '-' : '';
        $absolute = abs($this->cents);
        $dollars = number_format(intdiv($absolute, 100));
        $cents = str_pad((string) ($absolute % 100), 2, '0', STR_PAD_LEFT);

        return "{$sign}\${$dollars}.{$cents}";
    }
}
