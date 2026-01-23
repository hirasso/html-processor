<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM\QuoteLocalizer;

/**
 * Represents a quote character found in text during stack-based localization.
 *
 * Each match captures the quote's position and context to determine if it
 * can act as an opening or closing quote. The role is assigned later by
 * the stack algorithm based on nesting order.
 *
 * Context rules:
 * - canOpen: true if NOT preceded by a letter (e.g., start of word/string)
 * - canClose: true if NOT followed by a letter (e.g., end of word/string)
 * - Apostrophes like "don't" have both false → ignored by localizer
 */
final class QuoteMatch
{
    /** Assigned by stack algorithm: Open, Close, or null (unmatched) */
    public ?QuoteRole $role = null;

    /**
     * @param int $position Byte offset in the string
     * @param int $length Byte length of the quote character (1 for ASCII)
     * @param QuoteType $type Single or Double quote
     * @param bool $canOpen True if not preceded by a letter
     * @param bool $canClose True if not followed by a letter
     */
    public function __construct(
        public readonly int $position,
        public readonly int $length,
        public readonly QuoteType $type,
        public readonly bool $canOpen,
        public readonly bool $canClose,
    ) {
    }
}
