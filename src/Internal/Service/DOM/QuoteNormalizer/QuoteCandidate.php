<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Internal\Service\DOM\QuoteNormalizer;

/**
 * Represents a quote character found in text (before role assignment).
 */
final readonly class QuoteCandidate
{
    public function __construct(
        public string $char,
        public int $position,
        public bool $canOpen,
        public bool $canClose,
    ) {
    }
}
