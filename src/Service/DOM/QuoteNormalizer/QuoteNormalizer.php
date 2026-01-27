<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM\QuoteNormalizer;

use DOMNode;
use DOMText;
use Hirasso\HTMLProcessor\Service\Contract\DOMServiceContract;
use Hirasso\HTMLProcessor\Support\Support;
use IvoPetkov\HTML5DOMDocument;
use IvoPetkov\HTML5DOMElement;

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

    /** Current parent element during segment processing */
    private DOMNode $currentParent;

    /** Original parent of the text node being replaced */
    private DOMNode $originalParent;

    /** Original text node being replaced */
    private DOMText $textNode;

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
        foreach (Support::getTextNodes($document) as $textNode) {
            $nodeValue = $textNode->nodeValue ?? '';

            $text = Support::decode($nodeValue);
            $segments = $this->wrapper->wrapQuotes($text);

            // Check if we have any quote segments
            if ($this->hasQuoteSegments($segments)) {
                $this->replaceWithSegments($document, $textNode, $segments);
            }
        }
    }

    /**
     * Check if segments contain any quote markers
     *
     * @param Segment[] $segments
     */
    private function hasQuoteSegments(array $segments): bool
    {
        foreach ($segments as $segment) {
            if ($segment->getType() !== 'text') {
                return true;
            }
        }
        return false;
    }

    /**
     * Replace a text node with DOM nodes built from segments
     *
     * Example for "outer 'inner' outer":
     * ┌────────────┬───────────────────────┬────────────────┐
     * │    Step    │ $this->currentParent  │   $ancestors   │
     * ├────────────┼───────────────────────┼────────────────┤
     * │ Start      │ <p>                   │ []             │
     * ├────────────┼───────────────────────┼────────────────┤
     * │ QuoteOpen  │ <q>                   │ [<p>]          │
     * ├────────────┼───────────────────────┼────────────────┤
     * │ QuoteOpen  │ <q> (inner)           │ [<p>, <q>]     │
     * ├────────────┼───────────────────────┼────────────────┤
     * │ QuoteClose │ <q> (outer)           │ [<p>]          │
     * ├────────────┼───────────────────────┼────────────────┤
     * │ QuoteClose │ <p>                   │ []             │
     * └────────────┴───────────────────────┴────────────────┘
     *
     * @param Segment[] $segments
     */
    private function replaceWithSegments(HTML5DOMDocument $document, DOMText $textNode, array $segments): void
    {
        if (!$parent = $textNode->parentNode) {
            return;
        }

        /** @var HTML5DOMElement[] $ancestors Ancestor elements for nested <q> tags */
        $ancestors = [];
        $this->originalParent = $parent;
        $this->currentParent = $parent;
        $this->textNode = $textNode;

        foreach ($segments as $segment) {
            switch ($segment->getType()) {
                case 'open':
                    if ($qElement = $document->createElement('q')) {
                        $this->insertAtCurrentPosition($qElement);
                        $ancestors[] = $this->currentParent;
                        $this->currentParent = $qElement;
                    }
                    break;

                case 'close':
                    $this->currentParent = array_pop($ancestors) ?? $this->currentParent;
                    break;

                case 'text':
                    /** @var TextSegment $segment */
                    $this->insertAtCurrentPosition(
                        $document->createTextNode(Support::encode($segment->text, usePlaceholders: true))
                    );
                    break;
            }
        }

        /** Remove the original text node (now replaced by new structure) */
        $parent->removeChild($textNode);
    }

    /**
     * Insert a node at the current position.
     * Before descending into nested <q> elements, inserts before the original text node.
     * Inside nested <q> elements, appends to the current parent.
     */
    private function insertAtCurrentPosition(DOMNode $node): void
    {
        $this->currentParent === $this->originalParent
            ? $this->currentParent->insertBefore($node, $this->textNode)
            : $this->currentParent->appendChild($node);
    }
}
