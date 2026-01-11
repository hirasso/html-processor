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

        if (!$textNodes = $xPath->query('//text()')) {
            return;
        };

        foreach ($textNodes as $textNode) {
            // Skip text nodes inside <a> elements
            $ancestors = $xPath->query('ancestor::a', $textNode);
            if (!!$ancestors && $ancestors->length) {
                continue;
            }

            $nodeValue = $textNode->nodeValue ?? '';

            if (!str_contains($nodeValue, $this->prefix)) {
                continue;
            }

            $quotedPrefix = preg_quote($this->prefix);

            $result = preg_replace_callback(
                pattern: "/(?<=^|\s)$quotedPrefix(.*?)(?=\s|$)/",
                callback: function ($matches) use ($baseURL) {
                    [, $captured] = $matches;
                    return "<a href=\"$baseURL/$captured\">{$this->prefix}{$captured}</a>";
                },
                subject: $nodeValue
            );

            $textNode->nodeValue = $result ?? $nodeValue;
        }
    }
}
