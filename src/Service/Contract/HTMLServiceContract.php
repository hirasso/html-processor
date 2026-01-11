<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\Contract;

interface HTMLServiceContract
{
    /**
     * Execute the HTML operation
     */
    public function run(string $html): string;
}
