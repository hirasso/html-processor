<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\Contract;

use Dom\HTMLDocument;

interface DOMServiceContract
{
    public function run(HTMLDocument $document): HTMLDocument;
    public function prio(): int;
}
