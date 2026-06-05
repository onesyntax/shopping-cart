<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Cart\Cart;
use App\Domain\Cart\CartRepository;
use App\Domain\Catalog\Item;
use App\Domain\Shared\Money;
use App\Domain\Shared\Quantity;
use App\Infrastructure\Persistence\Eloquent\Models\CartRecord;
use Illuminate\Support\Facades\DB;

/**
 * Eloquent-backed cart store. A cart is its owner row plus its line rows;
 * reading reconstructs the {@see Cart} aggregate through its public API, and
 * saving replaces the stored lines wholesale so removals persist.
 */
final class EloquentCartRepository implements CartRepository
{
    public function forOwner(string $ownerId): Cart
    {
        $record = CartRecord::query()->with('lines')->where('owner_id', $ownerId)->first();

        $cart = new Cart($ownerId);
        if ($record === null) {
            return $cart;
        }

        foreach ($record->lines as $line) {
            // Each line captured its own unit price when added, so rebuild from
            // that price rather than today's catalog price. The synthetic Item
            // only carries the name and price the cart line needs; its
            // description is irrelevant once the line exists.
            $item = new Item($line->item_name, $line->item_name, Money::ofCents($line->unit_price_cents));
            $cart->addItem($item, Quantity::of($line->quantity));
        }

        return $cart;
    }

    public function save(Cart $cart): void
    {
        DB::transaction(function () use ($cart) {
            $record = CartRecord::query()->firstOrCreate(['owner_id' => $cart->ownerId]);
            $record->lines()->delete();

            foreach ($cart->lines() as $line) {
                $record->lines()->create([
                    'item_name' => $line->itemName,
                    'unit_price_cents' => $line->unitPrice->cents,
                    'quantity' => $line->quantity->value,
                ]);
            }
        });
    }
}
