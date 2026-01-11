<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Queue;

use Hirasso\HTMLProcessor\Queue\Contract\DOMQueueContract;
use Hirasso\HTMLProcessor\Service\Contract\DOMServiceContract;

final class DOMQueue implements DOMQueueContract
{
    /** @var array<class-string<DOMServiceContract>, DOMServiceContract> */
    protected array $services = [];

    public function add(DOMServiceContract $service): void
    {
        $this->services[$service::class] = $service;
    }

    public function isEmpty(): bool
    {
        return empty($this->services);
    }

    /** @return array<string, DOMServiceContract> */
    public function all(): array
    {
        return $this->services;
    }
}
