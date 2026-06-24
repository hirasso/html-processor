<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM\ProcessLinksService;

use Closure;
use Hirasso\HTMLProcessor\Service\Contract\DOMServiceContract;
use Hirasso\HTMLProcessor\Service\Trait\HasDefaultPrio;
use Dom\Element;
use Dom\HTMLDocument;
use Override;

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
final readonly class ProcessLinksService implements DOMServiceContract
{
    use HasDefaultPrio;

    /**
     * @param ?Closure(Link $link): mixed $userCallback
     */
    public function __construct(private ?Closure $userCallback = null)
    {
    }

    /**
     * Run this service
     */
    #[Override]
    public function run(HTMLDocument $document): HTMLDocument
    {
        foreach ($document->querySelectorAll('a[href]') as $el) {
            $this->process($el);
        }

        return $document;
    }

    /**
     * Process an HTML link element
     */
    private function process(Element $el): void
    {
        $link = new Link($el);

        /**
         * Run the user callback if provided
         */
        if ($this->userCallback !== null) {
            ($this->userCallback)($link);
            return;
        }

        /**
         * No user callback, apply defaults
         */
        $link->addClasses();
    }

}
