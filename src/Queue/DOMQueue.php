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

        $document = new HTML5DOMDocument();
        $document->loadHTML(
            htmlspecialchars_decode(Support::encode($html)),
            /**
             * @TODO reactivate this if it is fixed upstream
             * https://github.com/ivopetkov/html5-dom-document-php/pull/65
             */
            // HTML5DOMDocument::ALLOW_DUPLICATE_IDS
        );

        $this->runServices($document);

        return Support::extractBodyHTML($document);
    }

    /**
     * Apply all registered services against a provided $document
     */
    public function runServices(HTML5DOMDocument $document): void
    {
        // Execute all DOM services
        foreach ($this->all() as $service) {
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
