<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM\QuoteNormalizer;

interface Segment
{
    /** @return 'text'|value-of<QuoteRole> */
    public function getType(): string;
}
