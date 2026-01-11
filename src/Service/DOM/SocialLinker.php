<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM;

use DOMXPath;
use Hirasso\HTMLProcessor\Service\Contract\DOMServiceContract;
use IvoPetkov\HTML5DOMDocument;

final readonly class SocialLinker implements DOMServiceContract
{
    public function __construct(
        protected string $prefix,
        protected string $baseURL,
    ) {
    }

    /**
     * Link a prefix to a URL
     */
    public function run(HTML5DOMDocument $document): void
    {
        $baseURL = rtrim($this->baseURL, '/');
        $xPath = new DOMXPath($document);

        foreach ($xPath->query('//text()') as $textNode) {
            // Skip text nodes inside <a> elements
            if ($xPath->query('ancestor::a', $textNode)->length) {
                continue;
            }

            if (!str_contains($textNode->nodeValue, $this->prefix)) {
                continue;
            }

            $quotedPrefix = preg_quote($this->prefix);

            $textNode->nodeValue = preg_replace_callback(
                "/(?<=^|\s)$quotedPrefix(.*?)(?=\s|$)/",
                function ($matches) use ($baseURL) {
                    [, $captured] = $matches;
                    return "<a href=\"$baseURL/$captured\">{$this->prefix}{$captured}</a>";
                },
                $textNode->nodeValue
            );
        }
    }
}
