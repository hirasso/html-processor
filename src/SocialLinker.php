<?php

/*
 * Copyright (c) Rasso Hilber
 * https://rassohilber.com
 */

declare(strict_types=1);

namespace Hirasso\HTMLProcessor;

final readonly class SocialLinker
{
    public function __construct(
        protected HTMLProcessor $processor,
    ) {
    }

    /**
     * Link a prefix to a URL
     */
    public function link(string $prefix, string $baseURL): void
    {
        $baseURL = rtrim($baseURL, '/');

        foreach ($this->processor->queryXPath('//text()') as $textNode) {
            // Skip text nodes inside <a> elements
            if ($this->processor->queryXPath('ancestor::a', $textNode)->length) {
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
