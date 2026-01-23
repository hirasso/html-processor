<?php

/*
* Copyright (c) 2025 Rasso Hilber
* https://rassohilber.com
*/

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM\QuoteLocalizer;

final readonly class QuotePair
{
    public function __construct(
        public string $opening,
        public string $closing,
    ) {
    }
}
