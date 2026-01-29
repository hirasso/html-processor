<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Internal\Service\DOM\QuoteNormalizer;

readonly class QuoteSegment implements Segment
{
    public function __construct(public QuoteRole $role)
    {
    }

    public function getType(): string
    {
        return $this->role->value;
    }
}
