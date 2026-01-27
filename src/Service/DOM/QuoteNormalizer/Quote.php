<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM\QuoteNormalizer;

/**
 * Represents a paired quote with assigned role.
 */
final readonly class Quote
{
    public function __construct(
        public int $position,
        public QuoteRole $role,
    ) {
    }
}
