<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Internal\Queue\Contract;

use Hirasso\HTMLProcessor\Internal\Service\Contract\DOMServiceContract;
use IvoPetkov\HTML5DOMDocument;

interface DOMQueueContract extends QueueContract
{
    public function add(DOMServiceContract $service): void;
    public function runServices(HTML5DOMDocument $document): void;
}
