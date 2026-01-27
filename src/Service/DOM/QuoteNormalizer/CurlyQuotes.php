<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM\QuoteNormalizer;

/**
 * All curly quote variants to normalize back to ASCII.
 */
final class CurlyQuotes
{
    /**
     * All UTF-8 curly quote variants (single and double).
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            // Single quotes
            "'",       // ASCII
            "\u{2018}", // '
            "\u{2019}", // '
            "\u{201A}", // ‚
            "\u{2039}", // ‹
            "\u{203A}", // ›
            // Double quotes
            '"',       // ASCII
            "\u{201C}", // "
            "\u{201D}", // "
            "\u{201E}", // „
            "\u{00AB}", // «
            "\u{00BB}", // »
        ];
    }
}
