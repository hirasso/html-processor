<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM;

use Asika\Autolink\Autolink;
use Asika\Autolink\AutolinkOptions;
use Hirasso\HTMLProcessor\Service\Contract\DOMServiceContract;
use Hirasso\HTMLProcessor\Support\Support;
use Dom\HTMLDocument;
use Override;

/**
 * Makes urls clickable
 */
final readonly class AutolinkUrlsService implements DOMServiceContract
{
    public function __construct(
        public AutolinkOptions $options,
    ) {
    }

    /** autolink has to happen before everything else */
    #[Override]
    public function prio(): int
    {
        return -10;
    }

    #[Override]
    public function run(HTMLDocument $document): HTMLDocument
    {
        $autolink = new Autolink($this->options);

        foreach (Support::getTextNodes($document) as $node) {
            if ($node->parentElement?->closest('a')) {
                continue;
            }
            $node->data = $autolink->convert($node->data);
            $node->data = $autolink->convertEmail($node->data);

            Support::hydrateTextNode($node);
        }

        return $document;
    }
}
