<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Support;

use Dom\Element;
use Dom\HTMLDocument;
use Dom\Text;
use Dom\XPath;
use Generator;

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
     * Decode a HTML string
     */
    public static function decode(string $html): string
    {
        return html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Create a document from a HTML string
     */
    public static function createDocument(string $html): HTMLDocument
    {
        return HTMLDocument::createFromString(
            htmlspecialchars_decode(Support::encode($html)),
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
     * Remove any whitespace-looking stuff from a html string
     * \s matches regular whitespace, \xc2\xa0 matches UTF-8 non-breaking space
     */
    public static function normalizeWhitespace(string $string): string
    {
        $string = preg_replace('/^[\s\xc2\xa0]*$/i', ' ', $string) ?? $string;
        $string = preg_replace('/^[\s\xc2\xa0]*&nbsp;[\s\xc2\xa0]*$/i', ' ', $string) ?? $string;
        $string = preg_replace('/\s+/', ' ', $string) ?? $string;
        return $string;
    }

    /**
     * Check if an element contains only white space and nothing else
     */
    public static function containsOnlyWhitespace(Element $el): bool
    {
        if (!self::containsOnlyText($el)) {
            return false;
        }

        $textContent = Support::normalizeWhitespace($el->textContent ?? '');

        return empty(trim($textContent));
    }

    /**
     * Check if an element only contains text
     */
    public static function containsOnlyText(Element $el): bool
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

    /** @return \Generator<Text> */
    public static function getTextNodes(HTMLDocument $doc): Generator
    {
        $xpath = new XPath($doc);
        foreach ($xpath->query('//text()') as $node) {
            if ($node instanceof Text && trim($node->nodeValue ?? '') !== '') {
                yield $node;
            }
        }
    }
}
