<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM\LinkProcessor;

use Closure;
use Hirasso\HTMLProcessor\Service\Contract\DOMServiceContract;
use IvoPetkov\HTML5DOMDocument;
use IvoPetkov\HTML5DOMElement;

/**
 * Process links in HTML. Currently detects these URL types
 *
 * - mailto
 * - tel
 * - #hash
 * - internal
 * - external
 * - file (has non-web extension)
 */
final readonly class LinkProcessor implements DOMServiceContract
{
    /**
     * @param ?Closure(Link $link, Closure $defaultHandler): mixed $userCallback
     */
    public function __construct(private ?Closure $userCallback = null)
    {
    }

    public function prio(): int
    {
        return 0;
    }

    /**
     * Run this service
     */
    public function run(HTML5DOMDocument $document): void
    {
        foreach ($document->querySelectorAll('a[href]') as $el) {
            $this->process($el);
        }
    }

    /**
     * Process an HTML link element
     */
    private function process(HTML5DOMElement $el): void
    {
        $link = new Link($el);

        $defaultHandler = $this->getDefaultHandler($link);

        if ($this->userCallback !== null) {
            ($this->userCallback)($link, $defaultHandler);
            return;
        }
    }

    /**
     * Get the default handler if there was no userCallback provided
     */
    private function getDefaultHandler(Link $link): Closure {

        return function() use ($link) {
            $link->el->classList->add("link--{$link->type->value}");
            if ($link->isLinkToFile()) {
                $link->el->classList->add("link--file link--ext--$link->extension");
            }
        };

    }



}
