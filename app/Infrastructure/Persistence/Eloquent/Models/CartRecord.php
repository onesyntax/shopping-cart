<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Eloquent row for a shopper's cart, owning its lines.
 *
 * @property string $owner_id
 */
final class CartRecord extends Model
{
    protected $table = 'carts';

    protected $guarded = [];

    public function lines(): HasMany
    {
        return $this->hasMany(CartLineRecord::class, 'cart_id');
    }
}
