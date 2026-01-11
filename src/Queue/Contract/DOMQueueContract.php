<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Queue\Contract;

use Hirasso\HTMLProcessor\Queue\DOMOperation;

interface DOMQueueContract extends QueueContract
{
    public function add(DOMOperation $operation): void;
}
