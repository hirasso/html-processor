<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Support;

use IvoPetkov\HTML5DOMDocument;
use IvoPetkov\HTML5DOMElement;

final class Support
{
    /**
     * Convert entities while preserving already-encoded entities
     */
    public static function encode(string $html, ?bool $usePlaceholders = false): string
    {
        $html = \htmlentities(
            string: $html,
            flags: ENT_QUOTES,
            encoding: 'UTF-8',
            double_encode: false
        );

        if ($usePlaceholders ?? false) {
            $html = self::entitiesToPlaceholders($html);
        }

        return $html;
    }

    /**
     * Decode a HTML string
     */
    public static function decode(string $html): string
    {
        $html = self::placeholdersToEntities($html);
        return html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Extract HTML from body
     */
    public static function extractBodyHTML(\DOMDocument|HTML5DOMDocument $document): string
    {
        $html = $document->saveHTML() ?: '';
        preg_match('/<body[^>]*>(?<content>.*?)<\/body>/is', $html, $matches);
        $html = $matches['content'] ?? '';
        $html = str_replace('="__BOOLEAN_TRUE__"', '', $html);
        return $html;
    }

    /**
     * Remove any whitespace-looking stuff from a html string
     * \s matches regular whitespace, \xc2\xa0 matches UTF-8 non-breaking space
     */
    public static function normalizeWhitespace(string $string): string
    {
        $string = str_replace("html5-dom-document-internal-entity1-nbsp-end", ' ', $string);
        $string = preg_replace('/^[\s\xc2\xa0]*$/i', ' ', $string) ?? $string;
        $string = preg_replace('/^[\s\xc2\xa0]*&nbsp;[\s\xc2\xa0]*$/i', ' ', $string) ?? $string;
        $string = preg_replace('/\s+/', ' ', $string) ?? $string;
        return $string;
    }

    /**
     * Check if an element contains only white space and nothing else
     */
    public static function containsOnlyWhitespace(HTML5DOMElement $el): bool
    {
        if (!self::containsOnlyText($el)) {
            return false;
        }

        $textContent = Support::normalizeWhitespace($el->textContent);

        return empty(trim($textContent));
    }

    /**
     * Check if an element only contains text
     */
    public static function containsOnlyText(HTML5DOMElement $el): bool
    {
        if (!$el->hasChildNodes()) {
            return true;
        }

        foreach ($el->childNodes as $child) {
            if ($child->nodeType !== XML_TEXT_NODE) {
                return false;
            }
        }

        return true;
    }

    /**
     * Convert internal entity placeholders from HTML5DOMDocument back to the real entity
     */
    public static function placeholdersToEntities(string $html): string
    {
        if (strpos($html, 'html5-dom-document-internal-entity') === false) {
            return $html;
        }
        $html = preg_replace('/html5-dom-document-internal-entity1-(.*?)-end/', '&$1;', $html) ?? $html;
        $html = preg_replace('/html5-dom-document-internal-entity2-(.*?)-end/', '&#$1;', $html) ?? $html;
        return $html;
    }

    /**
     * Convert special chars to entities and then to the internal format used by HTML5DOMDocument
     */
    public static function entitiesToPlaceholders(string $html): string
    {
        // First, remove all placeholders
        $html = self::placeholdersToEntities($html);

        // Re-encode to normalize numeric entities (&#8220;) and named entities (&ldquo;) to UTF-8
        $html = self::decode($html);
        $html = self::encode($html);

        // replace named entities with placeholders
        $html = preg_replace('/&([a-zA-Z]+);/', 'html5-dom-document-internal-entity1-$1-end', $html) ?? $html;
        // replace numeric entities with placeholders
        $html = preg_replace('/&#(\d+);/', 'html5-dom-document-internal-entity2-$1-end', $html) ?? $html;

        return $html;
    }
}
