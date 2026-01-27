<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM\QuoteNormalizer;

enum QuoteRole: string
{
    case Open = "open";
    case Close = "close";
}
