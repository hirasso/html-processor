<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\Trait;

trait HasDefaultPrio
{
    public function prio(): int
    {
        return 0;
    }
}
