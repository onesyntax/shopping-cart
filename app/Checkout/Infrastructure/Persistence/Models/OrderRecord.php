<?php

declare(strict_types=1);

namespace App\Checkout\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Eloquent row for a placed order, owning its line snapshot.
 *
 * @property string $reference
 * @property string $owner_id
 * @property string $payment_method
 * @property string $status
 * @property ?string $payment_reference
 * @property ?string $deposit_reference
 * @property ?string $deposit_date
 */
final class OrderRecord extends Model
{
    protected $table = 'orders';

    protected $guarded = [];

    public function lines(): HasMany
    {
        return $this->hasMany(OrderLineRecord::class, 'order_id');
    }
}
