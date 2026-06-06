<?php

declare(strict_types=1);

namespace App\Foundation\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent row for a per-prefix reference counter.
 *
 * @property string $prefix
 * @property int $value
 */
final class ReferenceSequenceRecord extends Model
{
    protected $table = 'reference_sequences';

    protected $primaryKey = 'prefix';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = ['value' => 'int'];
}
