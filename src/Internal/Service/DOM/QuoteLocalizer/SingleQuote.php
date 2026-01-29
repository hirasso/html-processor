<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Internal\Service\DOM\QuoteLocalizer;

/**
 * UTF-8 quotation mark variants.
 *
 * Intended for normalization and sanitization tasks.
 */
enum SingleQuote: string implements QuoteContract
{
    case ASCII       = "'";

    // Curly single quotes
    case LEFT        = "\u{2018}"; // '
    case RIGHT       = "\u{2019}"; // '
    case LOW_9       = "\u{201A}"; // ‚
    case LEFT_ANGLE  = "\u{2039}"; // ‹
    case RIGHT_ANGLE = "\u{203A}"; // ›

    /**
     * All UTF-8 quote variants.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return array_map(
            static fn (self $quote) => $quote->value,
            self::cases()
        );
    }
}
