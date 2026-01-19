<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM;

use Asika\Autolink\Autolink;
use Asika\Autolink\AutolinkOptions;
use DOMNode;
use DOMXPath;
use Hirasso\HTMLProcessor\Service\Contract\DOMServiceContract;
use IvoPetkov\HTML5DOMDocument;

/**
 * Makes urls clickable
 */
final readonly class Autolinker implements DOMServiceContract
{
    public function __construct(
        public AutolinkOptions $options,
    ) {
    }

    /** autolink has to happen before everything else */
    public function prio(): int
    {
        return -10;
    }

    public function run(HTML5DOMDocument $document): void
    {
        $xPath = new DOMXPath($document);

        if (!$textNodes = $xPath->query('//text()')) {
            return;
        };

        $autolink = new Autolink($this->options);

        foreach ($textNodes as $node) {
            if (!($node instanceof DOMNode)) {
                continue;
            }

            $converted = $autolink->convert($node->textContent);
            $converted = $autolink->convertEmail($converted);
            $node->textContent = $converted;
        }
    }
}
