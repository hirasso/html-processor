<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM;

use Asika\Autolink\Autolink;
use Asika\Autolink\AutolinkOptions;
use Hirasso\HTMLProcessor\Service\Contract\DOMServiceContract;
use Hirasso\HTMLProcessor\Support\Support;
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
        static $autolink;

        if (!isset($autolink)) {
            $autolink = new Autolink($this->options);
        }

        foreach (Support::getTextNodes($document) as $node) {
            $converted = $autolink->convert($node->textContent);
            $converted = $autolink->convertEmail($converted);
            $node->textContent = $converted;
        }
    }
}
