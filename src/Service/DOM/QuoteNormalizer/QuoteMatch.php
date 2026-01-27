<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM\QuoteNormalizer;

/**
 * Represents a quote character found in text.
 *
 * Context rules:
 * - canOpen: true if NOT preceded by a letter
 * - canClose: true if NOT followed by a letter
 * - Apostrophes like "don't" have both false → ignored
 */
final class QuoteMatch
{
    /** Assigned by stack algorithm: Open, Close, or null (unmatched) */
    public ?QuoteRole $role = null;

    public function __construct(
        public readonly int $position,
        public readonly bool $canOpen,
        public readonly bool $canClose,
    ) {
    }
}
