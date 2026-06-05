<?php

declare(strict_types=1);

namespace App\Checkout\Domain;

/**
 * The result of attempting a card charge: an outcome and, when approved, the
 * gateway's transaction reference.
 */
final class PaymentResult
{
    private function __construct(
        public readonly PaymentOutcome $outcome,
        public readonly ?string $reference = null,
    ) {}

    public static function approved(string $reference): self
    {
        return new self(PaymentOutcome::Approved, $reference);
    }

    public static function declined(): self
    {
        return new self(PaymentOutcome::Declined);
    }

    public static function unreachable(): self
    {
        return new self(PaymentOutcome::Unreachable);
    }

    public function isApproved(): bool
    {
        return $this->outcome === PaymentOutcome::Approved;
    }

    public function isDeclined(): bool
    {
        return $this->outcome === PaymentOutcome::Declined;
    }

    public function isUnreachable(): bool
    {
        return $this->outcome === PaymentOutcome::Unreachable;
    }
}
