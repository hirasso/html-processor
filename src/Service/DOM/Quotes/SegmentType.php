<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM\Quotes;

/**
 * Type of segment in wrapped quote output.
 */
enum SegmentType
{
    case Text;
    case QuoteOpen;
    case QuoteClose;
}
