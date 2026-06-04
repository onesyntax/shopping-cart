<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent row for a catalog item. A persistence detail of the Infrastructure
 * layer — the Domain never sees this class.
 *
 * @property string $name
 * @property string $description
 * @property int $price_cents
 */
final class CatalogItemRecord extends Model
{
    protected $table = 'catalog_items';

    protected $guarded = [];

    protected $casts = ['price_cents' => 'int'];
}
