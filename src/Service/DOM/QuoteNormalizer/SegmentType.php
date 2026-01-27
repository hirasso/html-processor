<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM\QuoteNormalizer;

/**
 * Type of segment in wrapped quote output.
 */
enum SegmentType
{
    case Text;
    case QuoteOpen;
    case QuoteClose;
}
