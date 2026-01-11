<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Enum;

enum UrlType
{
    case Internal;
    case External;
    case Invalid;
}
