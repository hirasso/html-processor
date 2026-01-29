<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Internal\Service\DOM;

use DOMXPath;
use Hirasso\HTMLProcessor\Internal\Service\Contract\DOMServiceContract;
use Hirasso\HTMLProcessor\Internal\Support\Support;
use IvoPetkov\HTML5DOMDocument;

final class PrefixLinker implements DOMServiceContract
{
    /** @var array<string, string> */
    private array $entries;

    public function prio(): int
    {
        return 0;
    }

    /** Register an entry for linking */
    public function register(string $prefix, string $url): void
    {
        $this->entries[$prefix] = $this->normalizeURL($url);
    }

    /**
     * Normalize the URL
     */
    private function normalizeURL(string $url): string
    {
        $url = trim($url);

        $parts = parse_url($url);

        $hasQuery = isset($parts['query']);
        $endsWithSlash = str_ends_with($url, '/');
        $endsWithAt = str_ends_with($url, '@');

        if (!$hasQuery && !$endsWithSlash && !$endsWithAt) {
            $url .= '/';
        }
        return $url;
    }

    /**
     * Link a prefix to a URL
     */
    public function run(HTML5DOMDocument $document): void
    {
        $xPath = new DOMXPath($document);

        foreach (Support::getTextNodes($document) as $textNode) {
            // Skip text nodes inside <a> elements
            $ancestors = $xPath->query('ancestor::a', $textNode) ?: null;
            if ($ancestors?->length) {
                continue;
            }

            $text = $textNode->nodeValue ?? '';

            foreach ($this->entries as $prefix => $url) {
                $text = $this->link($text, $prefix, $url);
            }

            $textNode->nodeValue = $text;
        }
    }

    private function link(string $text, string $prefix, string $url): string
    {
        if (!str_contains($text, $prefix)) {
            return $text;
        }

        $quotedPrefix = preg_quote($prefix);

        $result = preg_replace_callback(
            pattern: "/(?<=^|\s)$quotedPrefix(.*?)(?=\s|$)/",
            callback: function ($matches) use ($prefix, $url) {
                [, $captured] = $matches;
                return "<a href=\"{$url}{$captured}\">{$prefix}{$captured}</a>";
            },
            subject: $text
        );

        return $result ?? $text;
    }
}
