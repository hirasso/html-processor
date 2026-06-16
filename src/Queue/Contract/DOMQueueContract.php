<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Queue\Contract;

use Hirasso\HTMLProcessor\Service\Contract\DOMServiceContract;
use Dom\HTMLDocument;

interface DOMQueueContract extends QueueContract
{
    public function add(DOMServiceContract $service): void;
    public function runServices(HTMLDocument $document): void;
}
