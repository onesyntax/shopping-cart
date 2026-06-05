<?php

declare(strict_types=1);

namespace App\Checkout\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent row for an issued invoice.
 *
 * @property string $reference
 * @property string $order_reference
 * @property int $total_cents
 */
final class InvoiceRecord extends Model
{
    protected $table = 'invoices';

    protected $guarded = [];

    protected $casts = ['total_cents' => 'int'];
}
