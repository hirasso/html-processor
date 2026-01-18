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
    public static function encode(string $html): string
    {
        return \htmlentities(
            string: $html,
            flags: ENT_QUOTES,
            encoding: 'UTF-8',
            double_encode: false
        );
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
}
