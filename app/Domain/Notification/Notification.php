<?php

declare(strict_types=1);

namespace App\Domain\Notification;

use App\Domain\Checkout\Order;

/**
 * A message sent to a shopper about the fate of their checkout. It carries the
 * order reference and the lines involved so the recipient can be told exactly
 * what they bought (or what failed).
 */
final class Notification
{
    /**
     * @param  list<NotificationLine>  $lines
     */
    public function __construct(
        public readonly string $recipient,
        public readonly NotificationKind $kind,
        public readonly ?string $orderReference = null,
        public readonly array $lines = [],
        public readonly ?string $reason = null,
    ) {}

    public static function orderPaid(Order $order): self
    {
        return new self($order->ownerId, NotificationKind::OrderPaid, $order->reference, self::linesOf($order));
    }

    public static function awaitingDepositConfirmation(Order $order): self
    {
        return new self(
            $order->ownerId,
            NotificationKind::AwaitingDepositConfirmation,
            $order->reference,
            self::linesOf($order),
        );
    }

    public static function awaitingPaymentOnDelivery(Order $order): self
    {
        return new self(
            $order->ownerId,
            NotificationKind::AwaitingPaymentOnDelivery,
            $order->reference,
            self::linesOf($order),
        );
    }

    public static function paymentFailed(string $recipient, string $reason): self
    {
        return new self($recipient, NotificationKind::PaymentFailed, reason: $reason);
    }

    public function quantityOf(string $itemName): int
    {
        foreach ($this->lines as $line) {
            if ($line->itemName === $itemName) {
                return $line->quantity;
            }
        }

        return 0;
    }

    /**
     * @return list<NotificationLine>
     */
    private static function linesOf(Order $order): array
    {
        return array_map(
            static fn ($line) => new NotificationLine($line->itemName, $line->quantity->value),
            $order->lines,
        );
    }
}
