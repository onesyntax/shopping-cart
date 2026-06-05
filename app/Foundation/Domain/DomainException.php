<?php

declare(strict_types=1);

namespace App\Foundation\Domain;

use RuntimeException;

/**
 * Base class for all domain-level rule violations. Carrying a plain message
 * keeps the Domain free of framework concerns; outer layers translate these
 * into HTTP responses, validation errors, or test assertions.
 */
class DomainException extends RuntimeException {}
