<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM\QuoteNormalizer;

/**
 * Represents a quote character found in text (before role assignment).
 *
 * Context rules:
 * - canOpen: true if NOT preceded by a letter
 * - canClose: true if NOT followed by a letter
 * - Apostrophes like "don't" have both false → ignored
 */
final readonly class QuoteCandidate
{
    public function __construct(
        public int $position,
        public bool $canOpen,
        public bool $canClose,
    ) {
    }
}
