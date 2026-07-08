<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Queue;

use Hirasso\HTMLProcessor\Queue\Contract\DomQueueContract;
use Hirasso\HTMLProcessor\Service\Contract\DomServiceContract;
use Hirasso\HTMLProcessor\Support\Support;
use Dom\HTMLDocument;

final class DomQueue implements DomQueueContract
{
    /** @var array<class-string<DomServiceContract>, DomServiceContract> */
    private array $services = [];

    public function add(DomServiceContract $service): void
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

    /**
     * Run all registered services against a document
     */
    public function applyTo(string $html): string
    {
        if ($this->isEmpty()) {
            return $html;
        }

        $document = Support::createDocument($html);

        $this->runServices($document);

        return Support::extractBodyHTML($document);
    }

    /**
     * Apply all registered services against a provided $document
     */
    public function runServices(HTMLDocument $document): void
    {
        // Execute all Dom services
        foreach ($this->services as $service) {
            $service->run($document);
        }
    }

}
