<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Internal\Service\DOM\QuoteLocalizer;

/**
 * UTF-8 quotation mark variants.
 *
 * Intended for normalization and sanitization tasks.
 */
enum DoubleQuote: string implements QuoteContract
{
    case ASCII       = '"';

    // Curly double quotes
    case LEFT        = "\u{201C}"; // "
    case RIGHT       = "\u{201D}"; // "
    case LOW_9       = "\u{201E}"; // „
    case LEFT_ANGLE  = "\u{00AB}"; // «
    case RIGHT_ANGLE = "\u{00BB}"; // »

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
