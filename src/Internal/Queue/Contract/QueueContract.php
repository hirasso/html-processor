<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Internal\Queue\Contract;

interface QueueContract
{
    public function isEmpty(): bool;

    /**
     * @template T of object
     * @param class-string<T> $className
     * @return T|null
     */
    public function get(string $className): ?object;
}
