<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Queue\Contract;

use Hirasso\HTMLProcessor\Service\Contract\DOMServiceContract;

interface DOMQueueContract extends QueueContract
{
    public function add(DOMServiceContract $service): void;
}
