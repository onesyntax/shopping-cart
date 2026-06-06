<?php

declare(strict_types=1);

namespace App\Infrastructure\Payment;

use App\Domain\Checkout\Card;
use App\Domain\Checkout\CardPaymentGateway;
use App\Domain\Checkout\PaymentResult;
use App\Domain\Shared\Money;
use App\Domain\Shared\ReferenceGenerator;

/**
 * A stand-in card gateway driven by the card token: "declined" and
 * "unreachable" simulate those failures; anything else is approved with a fresh
 * transaction reference. A real adapter would call out to a payment provider.
 */
final class FakeCardPaymentGateway implements CardPaymentGateway
{
    public const DECLINED = 'declined';

    public const UNREACHABLE = 'unreachable';

    public const VALID = 'valid';

    public function __construct(private readonly ReferenceGenerator $references) {}

    public function charge(Money $amount, Card $card): PaymentResult
    {
        return match ($card->token) {
            self::DECLINED => PaymentResult::declined(),
            self::UNREACHABLE => PaymentResult::unreachable(),
            default => PaymentResult::approved($this->references->next('PAY')),
        };
    }
}
