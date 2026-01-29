<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Internal\Service\DOM\QuoteLocalizer;

final readonly class QuotePair
{
    public function __construct(
        public string $opening,
        public string $closing,
    ) {
    }
}
