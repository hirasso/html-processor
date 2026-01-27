<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Queue;

use Hirasso\HTMLProcessor\Queue\Contract\DOMQueueContract;
use Hirasso\HTMLProcessor\Service\Contract\DOMServiceContract;
use Hirasso\HTMLProcessor\Support\Support;
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

        // Remove duplicate IDs before loading
        $html = $this->removeDuplicateIds($html);

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

    /**
     * Remove duplicate id attributes from HTML, keeping only first occurrence
     */
    private function removeDuplicateIds(string $html): string
    {
        $seenIds = [];

        // Match id attributes: id="value", id='value', id=value
        $pattern = '/\sid\s*=\s*(["\']?)([^"\'>\s]+)\1/i';

        $result = preg_replace_callback(
            $pattern,
            function ($matches) use (&$seenIds) {
                $idValue = $matches[2];

                // First occurrence: keep it
                if (!isset($seenIds[$idValue])) {
                    $seenIds[$idValue] = true;
                    return $matches[0];
                }

                // Duplicate: remove entire id attribute
                return '';
            },
            $html
        );

        return $result ?? $html;
    }
}
