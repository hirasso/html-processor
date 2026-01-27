<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM\QuoteLocalizer;

/**
 * Quotation mark variants.
 *
 * Intended for normalization and sanitization tasks.
 */
interface QuoteContract
{
    /**
     * All quote variants.
     *
     * @return list<string>
     */
    public static function all(): array;
}
