<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Support;

use Dom\DocumentFragment;
use Dom\HTMLDocument;
use Dom\Node;
use Dom\Text;
use Dom\XPath;

final class Support
{
    /**
     * Create a document from a HTML string
     */
    public static function createDocument(string $html): HTMLDocument
    {
        return HTMLDocument::createFromString(
            $html,
            LIBXML_NOERROR,
        );
    }

    /**
     * Extract the innerHTML from a document's <body>
     */
    public static function extractBodyHTML(HTMLDocument $document): string
    {
        return $document->body->innerHTML ?? '';
    }

    /**
     * Parse the text in a text node, if it contains HTML
     */
    public static function parseHtml(string $html): ?DocumentFragment
    {
        // only makes sense if the data actually contains HTML tags
        if (!str_contains($html, '<')) {
            return null;
        }

        $doc = HTMLDocument::createFromString($html, LIBXML_NOERROR);

        $newNodes = [...$doc->body->childNodes ?? []];

        if (empty($newNodes)) {
            return null; // @codeCoverageIgnore
        }

        $fragment = $doc->createDocumentFragment();

        // HTML parsers strip leading whitespace from <body>; restore it manually
        if (preg_match('/^(\s+)/', $html, $m)) {
            $fragment->append($doc->createTextNode($m[1]));
        }

        $fragment->append(...$newNodes);

        return $fragment;
    }

    /**
     * Normalize any whitespace-looking stuff from a html string
     * \s matches regular whitespace, \xc2\xa0 matches UTF-8 non-breaking space
     */
    public static function normalizeWhitespace(string $string): string
    {
        $string = preg_replace('/&nbsp;/', ' ', $string) ?? $string;
        $string = preg_replace('/^[\s\xc2\xa0]*$/i', ' ', $string) ?? $string;
        $string = preg_replace('/\s+/', ' ', $string) ?? $string;
        return $string;
    }

    /**
     * Check if a node contains only white space
     */
    public static function containsOnlyWhitespace(Node $node): bool
    {
        /** any child that is not a text node? */
        foreach ($node->childNodes as $childNode) {
            if (!($childNode instanceof Text)) {
                return false;
            }
        }

        return self::isOnlyWhitespace($node->textContent ?? '');
    }

    /**
     * Check if a string consists of only whitespace
     */
    private static function isOnlyWhitespace(string $value): bool
    {
        $value = self::normalizeWhitespace($value);

        return !self::isNonEmptyString(trim($value));
    }

    /**
     * Is a value a non-empty string?
     */
    private static function isNonEmptyString(mixed $value): bool
    {
        return is_string($value) && $value !== '';
    }

    /**
     * Check if an element only contains text
     */
    public static function elementContainsOnlyText(Node $el): bool
    {

        foreach ($el->childNodes as $child) {
            if (!($child instanceof Text)) {
                return false;
            }
        }

        return true;
    }

    /** @return list<\Dom\Text> */
    public static function getTextNodes(HTMLDocument $doc): array
    {
        /** @var list<\Dom\Text> */
        return array_values(array_filter(
            [...new XPath($doc)->query('//text()[normalize-space() != ""]')],
            fn ($node) => !$node->parentElement?->closest(
                'script, style, svg, noscript, title, textarea, select, iframe, canvas'
            )
        ));
    }

    /**
     * Trim lines from a string of text
     */
    public static function trimLines(string $text): string
    {
        return implode("\n", array_map(
            'trim',
            preg_split("/\R/", $text) ?: []
        ));
    }

    /**
     * Trim whitespace from a string of text
     */
    public static function trimWhitespace(string $text): string
    {
        return str_replace("\n", '', self::trimLines($text));
    }
}
