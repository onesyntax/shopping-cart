<?php

declare(strict_types=1);

namespace App\Foundation\Domain;

/**
 * Produces unique references for orders, invoices and payment transactions.
 * Implemented in an outer layer; the Domain only depends on this contract.
 */
interface ReferenceGenerator
{
    public function next(string $prefix): string;
}
