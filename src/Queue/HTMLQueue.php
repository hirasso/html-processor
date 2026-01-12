<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Queue;

use Hirasso\HTMLProcessor\Queue\Contract\HTMLQueueContract;
use Hirasso\HTMLProcessor\Service\Contract\HTMLServiceContract;

final class HTMLQueue implements HTMLQueueContract
{
    /** @var array<class-string<HTMLServiceContract>, HTMLServiceContract> */
    protected array $services = [];

    public function add(HTMLServiceContract $service): void
    {
        $this->services[$service::class] = $service;

        uasort($this->services, function ($a, $b) {
            return $a->prio() <=> $b->prio();
        });
    }

    public function isEmpty(): bool
    {
        return empty($this->services);
    }

    /** @return array<string, HTMLServiceContract> */
    public function all(): array
    {
        return $this->services;
    }

    /**
     * @template T of object
     * @param class-string<T> $className
     * @return T|null
     */
    public function get(string $className): ?object
    {
        /** @var T|null */
        return $this->services[$className] ?? null;
    }
}
