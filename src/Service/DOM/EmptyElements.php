<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM;

use Hirasso\HTMLProcessor\Service\Contract\DOMServiceContract;
use Hirasso\HTMLProcessor\Support\Support;
use Dom\HTMLDocument;

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

    public function run(HTMLDocument $document): void
    {
        $this->removeEmptyElements($document);
    }

    /**
     * Remove empty-looking paragraphs from html
     */
    private function removeEmptyElements(HTMLDocument $document): void
    {
        foreach ($document->querySelectorAll($this->selector ?? 'p') as $el) {
            if (Support::elementContainsOnlyWhitespace($el)) {
                $el->remove();
            }
        }
    }
}
