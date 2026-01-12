<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Enum;

enum UrlType
{
    case Internal;
    case External;
    case Mailto;
    case Tel;
    case Anchor;
    case Invalid;

    public function value(): string
    {
        return strtolower($this->name);
    }
}
