<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM;

use DOMXPath;
use Hirasso\HTMLProcessor\Service\Contract\DOMServiceContract;
use IvoPetkov\HTML5DOMDocument;

final readonly class SocialLinker implements DOMServiceContract
{
    public string $baseURL;

    public function __construct(
        public string $prefix,
        string $baseURL,
    ) {
        $baseURL = trim($baseURL);
        if (!preg_match('/[@=\/]$/', $baseURL)) {
            $baseURL = "$baseURL/";
        }
        $this->baseURL = $baseURL;
    }

    /**
     * Link a prefix to a URL
     */
    public function run(HTML5DOMDocument $document): void
    {
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
                callback: function ($matches) {
                    [, $captured] = $matches;
                    return "<a href=\"{$this->baseURL}{$captured}\">{$this->prefix}{$captured}</a>";
                },
                subject: $nodeValue
            );

            $textNode->nodeValue = $result ?? $nodeValue;
        }
    }
}
