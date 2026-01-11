<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Queue;

use Hirasso\HTMLProcessor\Queue\Contract\HTMLQueueContract;
use Hirasso\HTMLProcessor\Service\Contract\HTMLServiceContract;

final class HTMLQueue implements HTMLQueueContract
{
    /** @var array<string, HTMLServiceContract> */
    protected array $services = [];

    public function add(HTMLServiceContract $service): void
    {
        $this->services[$service->getName()] = $service;
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
}
