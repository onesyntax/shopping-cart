<?php

declare(strict_types=1);

namespace App\Checkout\Infrastructure\Persistence;

use App\Checkout\Domain\Order;
use App\Checkout\Domain\OrderLine;
use App\Checkout\Domain\OrderRepository;
use App\Checkout\Domain\OrderStatus;
use App\Checkout\Domain\PaymentMethod;
use App\Checkout\Infrastructure\Persistence\Models\OrderRecord;
use App\Foundation\Domain\Money;
use App\Foundation\Domain\Quantity;
use Illuminate\Support\Facades\DB;

/**
 * Eloquent-backed order store. Persists a placed order with its line snapshot
 * and reloads the most recent one for an owner as a whole {@see Order}.
 */
final class EloquentOrderRepository implements OrderRepository
{
    public function save(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $record = OrderRecord::query()->create([
                'reference' => $order->reference,
                'owner_id' => $order->ownerId,
                'payment_method' => $order->paymentMethod->value,
                'status' => $order->status->value,
                'payment_reference' => $order->paymentReference,
                'deposit_reference' => $order->depositReference,
                'deposit_date' => $order->depositDate,
            ]);

            foreach ($order->lines as $line) {
                $record->lines()->create([
                    'item_name' => $line->itemName,
                    'unit_price_cents' => $line->unitPrice->cents,
                    'quantity' => $line->quantity->value,
                ]);
            }
        });
    }

    public function latestForOwner(string $ownerId): ?Order
    {
        $record = OrderRecord::query()
            ->with('lines')
            ->where('owner_id', $ownerId)
            ->orderByDesc('id')
            ->first();

        if ($record === null) {
            return null;
        }

        $lines = $record->lines
            ->map(fn ($line) => new OrderLine(
                $line->item_name,
                Money::ofCents($line->unit_price_cents),
                Quantity::of($line->quantity),
            ))
            ->all();

        return new Order(
            reference: $record->reference,
            ownerId: $record->owner_id,
            lines: $lines,
            paymentMethod: PaymentMethod::from($record->payment_method),
            status: OrderStatus::from($record->status),
            paymentReference: $record->payment_reference,
            depositReference: $record->deposit_reference,
            depositDate: $record->deposit_date,
        );
    }
}
