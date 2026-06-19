<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Support;

use Dom\Element;
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
     * Convert a text node to HTML
     */
    public static function replaceTextNodeWithHtml(Text $textNode, string $html): void
    {
        if (self::containsOnlyWhitespace($html)) {
            return;
        }

        if ($html === $textNode->nodeValue) {
            return;
        }

        if (!$targetDoc = $textNode->ownerDocument) {
            return; // @codeCoverageIgnore
        }

        $tmpDoc = HTMLDocument::createFromString($html, LIBXML_NOERROR);

        if (!$tmpDoc->body) {
            return; // @codeCoverageIgnore
        }

        $fragment = $targetDoc->createDocumentFragment();

        foreach ($tmpDoc->body->childNodes as $child) {
            $fragment->appendChild($targetDoc->importNode($child, true));
        }

        $textNode->replaceWith($fragment);
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
    public static function elementContainsOnlyWhitespace(Element $el): bool
    {
        if (!self::elementContainsOnlyText($el)) {
            return false;
        }

        return self::containsOnlyWhitespace($el->textContent ?? '');
    }

    /**
     * Is a value only whitespace
     */
    private static function containsOnlyWhitespace(string $value): bool
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
    public static function elementContainsOnlyText(Element $el): bool
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

    public static function hasAncestor(\Dom\Node $node, string $tagName): bool
    {
        $parent = $node->parentNode;
        while ($parent !== null) {
            if ($parent instanceof Element && strtolower($parent->tagName) === strtolower($tagName)) {
                return true;
            }
            $parent = $parent->parentNode;
        }
        return false;
    }

    /** @return list<Text> */
    public static function getTextNodes(HTMLDocument $doc): array
    {
        $xpath = new XPath($doc);
        $nodes = array_filter(
            [...$xpath->query('//text()')],
            fn (Node $node) => $node instanceof Text && trim($node->nodeValue ?? '') !== ''
        );
        return array_values($nodes);
    }
}
