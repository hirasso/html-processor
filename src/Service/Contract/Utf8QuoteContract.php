<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\Contract;

/**
 * UTF-8 quotation mark variants.
 *
 * Intended for normalization and sanitization tasks.
 */
interface Utf8QuoteContract
{
    /**
     * All UTF-8 quote variants.
     *
     * @return list<string>
     */
    public static function all(): array;
}
