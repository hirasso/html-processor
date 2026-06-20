<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM;

use Hirasso\HTMLProcessor\Service\Contract\DOMServiceContract;
use Hirasso\HTMLProcessor\Service\Trait\HasDefaultPrio;
use Hirasso\HTMLProcessor\Support\Support;
use Dom\HTMLDocument;

/**
 * Remove empty-looking paragraphs from html
 */
final readonly class EmptyElementsRemover implements DOMServiceContract
{
    use HasDefaultPrio;

    public function __construct(
        private string $selector
    ) {
    }

    public function run(HTMLDocument $document): void
    {
        foreach ($document->querySelectorAll($this->selector) as $el) {
            if (Support::containsOnlyWhitespace($el)) {
                $el->remove();
            }
        }
    }
}
