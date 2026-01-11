<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Queue\Contract;

interface QueueContract
{
    public function isEmpty(): bool;

    /** @return array<string, mixed> */
    public function all(): array;
}
