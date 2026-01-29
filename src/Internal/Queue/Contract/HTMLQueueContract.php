<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Internal\Queue\Contract;

use Closure;
use Hirasso\HTMLProcessor\Internal\Service\Contract\HTMLServiceContract;

interface HTMLQueueContract extends QueueContract
{
    public function add(HTMLServiceContract $service): void;
    /** @param ?Closure(HTMLServiceContract): bool $filter */
    public function applyTo(string $html, ?Closure $filter = null): string;
}
