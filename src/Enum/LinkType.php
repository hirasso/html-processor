<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Enum;

enum LinkType: string
{
    case Internal = 'internal';
    case External = 'external';
    case Mailto = 'mailto';
    case Tel = 'tel';
    case Anchor = 'anchor';
    case Invalid = 'invalid';
}
