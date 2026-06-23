<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Queue;

use Hirasso\HTMLProcessor\Queue\Contract\DOMQueueContract;
use Hirasso\HTMLProcessor\Service\Contract\DOMServiceContract;
use Hirasso\HTMLProcessor\Support\Support;
use Dom\HTMLDocument;

final class DOMQueue implements DOMQueueContract
{
    /** @var array<class-string<DOMServiceContract>, DOMServiceContract> */
    private array $services = [];

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
        $this->injectDeobfuscationScript($document);

        return Support::extractBodyHTML($document);
    }

    /**
     * Inject the script that de-obfuscates obfuscated emails in the frontend
     */
    private function injectDeobfuscationScript(HTMLDocument $document): void
    {
        static $injected = false;
        if ($injected) {
            return;
        }
        $injected = true;

        $script = $document->createElement('script');
        $script->setAttribute('type', 'module');
        $script->textContent = file_get_contents(dirname(__DIR__, 2). '/resources/obfuscation.js') ?: '';
        $document->body?->append($script);
    }

    /**
     * Apply all registered services against a provided $document
     */
    public function runServices(HTMLDocument $document): void
    {
        // Execute all DOM services
        foreach ($this->services as $service) {
            $service->run($document);
        }
    }

}
