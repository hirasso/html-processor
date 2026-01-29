<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Internal\Service\DOM;

use Hirasso\HTMLProcessor\Internal\Service\Contract\DOMServiceContract;
use Hirasso\HTMLProcessor\Internal\Support\Support;
use IvoPetkov\HTML5DOMDocument;
use IvoPetkov\HTML5DOMElement;

final readonly class EmptyElements implements DOMServiceContract
{
    public function __construct(
        protected ?string $selector = null
    ) {
    }

    public function prio(): int
    {
        return 0;
    }

    public function run(HTML5DOMDocument $document): void
    {
        $this->removeEmptyElements($document);
    }

    /**
     * Remove empty-looking paragraphs from html
     */
    private function removeEmptyElements(HTML5DOMDocument $document): void
    {
        foreach ($document->querySelectorAll($this->selector ?? 'p') as $el) {
            /** @var HTML5DOMElement $el */
            if (Support::containsOnlyWhitespace($el)) {
                $el->parentNode?->removeChild($el);
            }
        }
    }
}
