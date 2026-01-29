<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Internal\Service\DOM\QuoteNormalizer;

readonly class TextSegment implements Segment
{
    public function __construct(public string $text)
    {
    }

    public function getType(): string
    {
        return "text";
    }
}
