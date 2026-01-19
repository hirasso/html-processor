<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Queue;

use Closure;
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

    public function applyTo(
        string $html,
        ?Closure $filter = null
    ): string {
        $services = $this->services;
        if ($filter) {
            $services = array_filter(
                $this->services,
                fn ($service) => ($filter)($service)
            );
        }
        foreach($services as $service) {
            $html = $service->run($html);
        }
        return $html;
    }
}
