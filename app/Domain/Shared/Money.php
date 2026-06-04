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

        // Accept either a plain integer part or one grouped into three-digit
        // thousands (e.g. "1,234,567"); reject misplaced commas. Strip the
        // separators only once the grouping is known to be well-formed.
        $ungrouped = '/^\d+(\.\d{1,2})?$/';
        $grouped = '/^\d{1,3}(,\d{3})+(\.\d{1,2})?$/';
        if (! preg_match($ungrouped, $text) && ! preg_match($grouped, $text)) {
            throw new DomainException("\"{$value}\" is not a valid money amount.");
        }

        $text = str_replace(',', '', $text);

        // The regex above guarantees a pure-digit whole part and a fraction of
        // at most two digits, so no truncation or numeric-cast guarding is
        // needed here: a missing fraction defaults to zero cents.
        $parts = explode('.', $text);
        $fraction = str_pad($parts[1] ?? '0', 2, '0');
        $cents = $parts[0] * 100 + $fraction;

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
