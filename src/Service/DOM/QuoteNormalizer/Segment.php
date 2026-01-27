<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM\QuoteNormalizer;

/**
 * Represents a segment in wrapped quote output.
 */
final readonly class Segment
{
    public function __construct(
        public SegmentType $type,
        public ?string $content = null,
    ) {
    }
}
