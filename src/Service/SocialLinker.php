<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service;

use DOMXPath;
use IvoPetkov\HTML5DOMDocument;

final readonly class SocialLinker
{
    public function __construct(
        protected HTML5DOMDocument $document,
    ) {
    }

    /**
     * Link a prefix to a URL
     */
    public function link(string $prefix, string $baseURL): void
    {
        $baseURL = rtrim($baseURL, '/');
        $xPath = new DOMXPath($this->document);

        foreach ($xPath->query('//text()') as $textNode) {
            // Skip text nodes inside <a> elements
            if ($xPath->query('ancestor::a', $textNode)->length) {
                continue;
            }

            if (!str_contains($textNode->nodeValue, $prefix)) {
                continue;
            }

            $quotedPrefix = preg_quote($prefix);

            $textNode->nodeValue = preg_replace_callback(
                "/(?<=^|\s)$quotedPrefix(.*?)(?=\s|$)/",
                function ($matches) use ($prefix, $baseURL) {
                    [, $captured] = $matches;
                    return "<a href=\"$baseURL/$captured\">{$prefix}{$captured}</a>";
                },
                $textNode->nodeValue
            );
        }
    }
}
