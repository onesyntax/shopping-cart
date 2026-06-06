<?php

declare(strict_types=1);

namespace App\Checkout\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent row for a single order line.
 *
 * @property int $order_id
 * @property string $item_name
 * @property int $unit_price_cents
 * @property int $quantity
 */
final class OrderLineRecord extends Model
{
    protected $table = 'order_lines';

    protected $guarded = [];

    protected $casts = [
        'unit_price_cents' => 'int',
        'quantity' => 'int',
    ];
}
