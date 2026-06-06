<?php

declare(strict_types=1);

namespace App\Foundation\Infrastructure;

use App\Foundation\Domain\ReferenceGenerator;
use App\Foundation\Infrastructure\Persistence\Models\ReferenceSequenceRecord;
use Illuminate\Support\Facades\DB;

/**
 * Generates references like "ORD-1", "INV-2" from a database-backed per-prefix
 * counter, so each reference is unique across HTTP requests — which an
 * in-process counter cannot guarantee once orders are persisted.
 *
 * The increment runs in a transaction with a row lock so concurrent checkouts
 * can never be handed the same number.
 */
final class DatabaseReferenceGenerator implements ReferenceGenerator
{
    public function next(string $prefix): string
    {
        $value = DB::transaction(function () use ($prefix) {
            $sequence = ReferenceSequenceRecord::query()
                ->lockForUpdate()
                ->firstOrCreate(['prefix' => $prefix], ['value' => 0]);

            $sequence->increment('value');

            return $sequence->value;
        });

        return "{$prefix}-{$value}";
    }
}
