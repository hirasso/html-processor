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

    public function prio(): int {
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
            if ($this->containsOnlyWhitespace($el)) {
                $el->parentNode?->removeChild($el);
            }
        }
    }

    /**
     * Check if an element contains only white space and nothing else
     */
    private function containsOnlyWhitespace(HTML5DOMElement $el): bool {
        if (!$this->containsOnlyText($el)) {
            return false;
        }

        $textContent = Helpers::normalizeWhitespace($el->textContent);

        return empty(trim($textContent));
    }

    /**
     * Check if an element only contains text
     */
    private function containsOnlyText(HTML5DOMElement $el): bool
    {
        if (!$el->hasChildNodes()) {
            return true;
        }

        foreach ($el->childNodes as $child) {
            if ($child->nodeType !== XML_TEXT_NODE) {
                return false;
            }
        }

        return true;
    }
}
