<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\Contract;

interface HTMLServiceContract
{
    public function run(string $html): string;
    public function prio(): int;
}
