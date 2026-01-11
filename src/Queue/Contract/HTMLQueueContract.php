<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Queue\Contract;

use Hirasso\HTMLProcessor\Queue\HTMLOperation;

interface HTMLQueueContract extends QueueContract
{
    public function add(HTMLOperation $operation): void;
}
