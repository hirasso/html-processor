<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM\QuoteNormalizer;

interface Segment
{
    /** @return 'text'|'open'|'close' */
    public function getType(): string;
}
