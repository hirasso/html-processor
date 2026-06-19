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
final readonly class EmptyElements implements DOMServiceContract
{
    use HasDefaultPrio;

    public function __construct(
        private ?string $selector = null
    ) {
    }

    public function run(HTMLDocument $document): void
    {
        foreach ($document->querySelectorAll($this->selector ?? 'p') as $el) {
            if (Support::containsOnlyWhitespace($el)) {
                $el->remove();
            }
        }
    }
}
