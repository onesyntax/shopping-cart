<?php

declare(strict_types=1);

namespace App\Cart\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent row for a single cart line.
 *
 * @property int $cart_id
 * @property string $item_name
 * @property int $unit_price_cents
 * @property int $quantity
 */
final class CartLineRecord extends Model
{
    protected $table = 'cart_lines';

    protected $guarded = [];

    protected $casts = [
        'unit_price_cents' => 'int',
        'quantity' => 'int',
    ];
}
