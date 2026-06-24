<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\Dom;

use Hirasso\HTMLProcessor\Service\Contract\DomServiceContract;
use Hirasso\HTMLProcessor\Service\Trait\HasDefaultPrio;
use Hirasso\HTMLProcessor\Support\Support;
use Dom\HTMLDocument;
use Override;

/**
 * Remove empty-looking paragraphs from html
 */
final readonly class RemoveEmptyElementsService implements DomServiceContract
{
    use HasDefaultPrio;

    public function __construct(
        private string $selector
    ) {
    }

    #[Override]
    public function run(HTMLDocument $document): void
    {
        foreach ($document->querySelectorAll($this->selector) as $el) {
            if (Support::containsOnlyWhitespace($el)) {
                $el->remove();
            }
        }
    }
}
