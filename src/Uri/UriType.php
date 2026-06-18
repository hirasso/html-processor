<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Uri;

enum UriType: string
{
    case Internal = 'internal';
    case External = 'external';
    case Mailto = 'mailto';
    case Tel = 'tel';
    case Invalid = 'invalid';
}
