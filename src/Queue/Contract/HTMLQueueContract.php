<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Queue\Contract;

use Hirasso\HTMLProcessor\Service\Contract\HTMLServiceContract;

interface HTMLQueueContract extends QueueContract
{
    public function add(HTMLServiceContract $service): void;
}
