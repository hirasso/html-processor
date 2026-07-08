<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Queue\Contract;

use Hirasso\HTMLProcessor\Service\Contract\DomServiceContract;
use Dom\HTMLDocument;

interface DomQueueContract extends QueueContract
{
    public function add(DomServiceContract $service): void;
    public function runServices(HTMLDocument $document): void;
}
