<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM\Quotes;

use DOMXPath;
use Hirasso\HTMLProcessor\Service\Contract\DOMServiceContract;
use Hirasso\HTMLProcessor\Support\Support;
use IvoPetkov\HTML5DOMDocument;

/**
 * Wraps quoted text in <q> HTML tags.
 *
 * Unlike QuoteLocalizer which replaces quotes with typographic characters,
 * this service wraps quoted content with semantic <q> elements that can be
 * styled via CSS.
 *
 * @see QuoteWrapper for the wrapping algorithm
 */
final class QuoteNormalizer implements DOMServiceContract
{
    private QuoteWrapper $wrapper;

    public function __construct()
    {
        $this->wrapper = new QuoteWrapper();
    }

    public function prio(): int
    {
        return 0;
    }

    /**
     * Run the normalizer on text nodes
     */
    public function run(HTML5DOMDocument $document): void
    {
        $xpath = new DOMXPath($document);
        $textNodes = $xpath->query('//text()');

        if (!$textNodes) {
            return;
        }

        // Collect all text nodes first (modifying DOM while iterating can cause issues)
        /** @var \DOMText[] $nodes */
        $nodes = [];
        foreach ($textNodes as $textNode) {
            if ($textNode instanceof \DOMText) {
                $nodes[] = $textNode;
            }
        }

        foreach ($nodes as $textNode) {
            $nodeValue = $textNode->nodeValue ?? '';

            if (empty(trim($nodeValue))) {
                continue;
            }

            $text = Support::decode($nodeValue);
            $wrapped = $this->wrapper->wrapQuotes($text);

            // If the text contains <q> tags, we need to replace with a document fragment
            if (str_contains($wrapped, '<q>')) {
                $this->replaceWithFragment($document, $textNode, $wrapped);
            } else {
                $textNode->nodeValue = Support::encode($wrapped, usePlaceholders: true);
            }
        }
    }

    /**
     * Replace a text node with HTML content (including <q> tags)
     */
    private function replaceWithFragment(HTML5DOMDocument $document, \DOMText $textNode, string $html): void
    {
        if (!$parent = $textNode->parentNode) {
            return;
        }

        // Create a temporary wrapper to parse the HTML with <q> tags
        if (!$body = Support::createDocument($html)->getElementsByTagName('body')->item(0)) {
            return;
        }

        // Import and insert each child node before the text node
        foreach ($body->childNodes as $child) {
            $imported = $document->importNode($child, true);
            $parent->insertBefore($imported, $textNode);
        }

        // Remove the original text node
        $parent->removeChild($textNode);
    }
}
