<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM\QuoteNormalizer;

/**
 * All curly quote variants to normalize back to ASCII.
 */
final class CurlyQuotes
{
    /**
     * All curly quote variants (single and double).
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            // ASCII
            "'",
            '"',

            // UTF-8 single
            "\u{2018}", // '
            "\u{2019}", // '
            "\u{201A}", // ‚
            "\u{2039}", // ‹
            "\u{203A}", // ›

            // UTF-8 double
            "\u{201C}", // "
            "\u{201D}", // "
            "\u{201E}", // „
            "\u{00AB}", // «
            "\u{00BB}", // »
        ];
    }
}
