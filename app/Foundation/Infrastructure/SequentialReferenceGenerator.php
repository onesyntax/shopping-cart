<?php

declare(strict_types=1);

namespace App\Foundation\Infrastructure;

use App\Foundation\Domain\ReferenceGenerator;

/**
 * Generates references like "ORD-1", "ORD-2", "INV-1" by keeping a per-prefix
 * counter. Good enough for a single-process run; a production adapter would
 * use the database or a UUID.
 */
final class SequentialReferenceGenerator implements ReferenceGenerator
{
    /** @var array<string, int> */
    private array $counters = [];

    public function next(string $prefix): string
    {
        $this->counters[$prefix] = ($this->counters[$prefix] ?? 0) + 1;

        return "{$prefix}-{$this->counters[$prefix]}";
    }
}
