<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Contracts;

use Hirasso\HTMLProcessor\Operations\HTMLOperation;

interface HTMLQueueContract extends QueueContract {
    public function add(HTMLOperation $operation): void;
}
