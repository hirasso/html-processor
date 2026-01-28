<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM\QuoteNormalizer;

/**
 * Represents a Quote with assigned role.
 */
final readonly class Quote
{
    public function __construct(
        public QuoteRole $role,
        public int $position,
        public string $char,
    ) {
    }
}
