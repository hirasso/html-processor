<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM;

use Hirasso\HTMLProcessor\Service\Contract\DOMServiceContract;
use Hirasso\HTMLProcessor\Support\Helpers;
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
            if (Helpers::containsOnlyWhitespace($el)) {
                $el->parentNode?->removeChild($el);
            }
        }
    }
}
