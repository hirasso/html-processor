<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Contracts;

use Hirasso\HTMLProcessor\Operations\DOMOperation;

interface DOMQueueContract extends QueueContract {
    public function add(DOMOperation $operation): void;
}
