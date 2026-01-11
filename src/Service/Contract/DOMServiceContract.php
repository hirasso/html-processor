<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\Contract;

use IvoPetkov\HTML5DOMDocument;

interface DOMServiceContract
{
    /**
     * Execute the DOM operation
     */
    public function run(HTML5DOMDocument $document): void;
}
