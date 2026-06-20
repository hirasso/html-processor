<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM;

use Dom\HTMLDocument;
use Dom\Text;
use Hirasso\HTMLProcessor\Service\Contract\DOMServiceContract;
use Hirasso\HTMLProcessor\Service\Trait\HasDefaultPrio;
use Hirasso\HTMLProcessor\Support\Support;

final class PrefixLinker implements DOMServiceContract
{
    use HasDefaultPrio;

    /** @var array<string, string> */
    private array $entries;

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
    public function run(HTMLDocument $document): void
    {
        foreach (Support::getTextNodes($document) as $node) {
            // Skip text nodes inside <a> elements
            if ($node->parentElement?->closest('a')) {
                continue;
            }

            foreach ($this->entries as $prefix => $url) {
                $node->data = $this->link($node->data, $prefix, $url);
            }

            if ($parsed = Support::parseTextNode($node)) {
                $node->replaceWith($parsed);
            };
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
