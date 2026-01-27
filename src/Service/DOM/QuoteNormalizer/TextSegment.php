<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM\QuoteNormalizer;

readonly class TextSegment implements Segment
{
    public function __construct(public string $content)
    {
    }

    public function getType(): string
    {
        return "text";
    }
}
