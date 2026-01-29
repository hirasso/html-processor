<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Internal\Queue;

use Hirasso\HTMLProcessor\Internal\Queue\Contract\DOMQueueContract;
use Hirasso\HTMLProcessor\Internal\Service\Contract\DOMServiceContract;
use Hirasso\HTMLProcessor\Internal\Support\Support;
use IvoPetkov\HTML5DOMDocument;

final class DOMQueue implements DOMQueueContract
{
    /** @var array<class-string<DOMServiceContract>, DOMServiceContract> */
    protected array $services = [];

    public function add(DOMServiceContract $service): void
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
    public function runServices(HTML5DOMDocument $document): void
    {
        // Execute all DOM services
        foreach ($this->services as $service) {
            $service->run($document);
        }
    }
}
