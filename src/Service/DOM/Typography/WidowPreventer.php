<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM\Typography;

use DOMNode;
use DOMXPath;
use Hirasso\HTMLProcessor\Service\Contract\DOMServiceContract;
use Hirasso\HTMLProcessor\Support\Support;
use IvoPetkov\HTML5DOMDocument;

final readonly class WidowPreventer implements DOMServiceContract
{
    public function prio(): int
    {
        return 0;
    }

    /**
     * Block-level elements
     */
    private const BLOCK_ELEMENTS = [
        'body', 'address', 'article', 'aside', 'blockquote', 'dd', 'div', 'dl', 'dt',
        'fieldset', 'figcaption', 'figure', 'footer', 'form', 'h1', 'h2', 'h3',
        'h4', 'h5', 'h6', 'header', 'hgroup', 'li', 'main', 'nav', 'ol', 'p',
        'pre', 'section', 'td', 'th', 'ul'
    ];

    /**
     * Prevent widows in html text
     */
    public function run(HTML5DOMDocument $document): void
    {
        $xPath = new DOMXPath($document);

        // Find all block elements
        $blockElementsQuery = implode(' | ', array_map(fn ($tag) => "//{$tag}", self::BLOCK_ELEMENTS));
        $blockElements = $xPath->query($blockElementsQuery);

        if ($blockElements === false) {
            return;
        }

        /**
         * For each block element that doesn't contain other block elements (leaf blocks),
         * find the last text node and apply widow prevention
         */
        foreach ($blockElements as $el) {
            if (!($el instanceof DOMNode)) {
                continue;
            }

            // Skip if this block contains other block elements
            if ($this->containsBlockElements($el, $xPath)) {
                continue;
            }

            $lastTextNode = $this->findLastTextNode($el);

            if ($lastTextNode !== null) {
                $lastTextNode->textContent = $this->maybePreventWidows($lastTextNode->textContent);
            }
        }
    }

    /**
     * Check if an element contains other block elements
     */
    private function containsBlockElements(\DOMNode $element, DOMXPath $xPath): bool
    {
        $blockElementsQuery = implode(' | ', array_map(fn ($tag) => ".//{$tag}", self::BLOCK_ELEMENTS));
        $childBlocks = $xPath->query($blockElementsQuery, $element);

        return $childBlocks !== false && $childBlocks->length > 0;
    }

    /**
     * Find the last non-empty text node within an element
     */
    private function findLastTextNode(\DOMNode $node): ?\DOMText
    {
        // Traverse children in reverse to find the last text node
        $lastTextNode = null;

        if ($node->hasChildNodes()) {
            for ($i = $node->childNodes->length - 1; $i >= 0; $i--) {
                $child = $node->childNodes->item($i);

                if ($child === null) {
                    continue;
                }

                // If it's a text node with content, use it
                if ($child instanceof \DOMText) {
                    $textContent = trim($child->textContent);
                    if (!empty($textContent)) {
                        return $child;
                    }
                }

                // Otherwise, recursively search this child
                if ($child->hasChildNodes()) {
                    $lastTextNode = $this->findLastTextNode($child);
                    if ($lastTextNode !== null) {
                        return $lastTextNode;
                    }
                }
            }
        }

        return $lastTextNode;
    }

    /**
     * Prevent widows in a string
     *
     * @see http://davidwalsh.name/prevent-widows-php-javascript
     */
    private function maybePreventWidows(string $textContent): string
    {
        // first remove any eventual white space
        $string = Support::normalizeWhitespace($textContent);

        if (empty(trim($string))) {
            return $textContent;
        }

        // count the words
        $words = explode(" ", $string);
        $wordCount = count($words);

        // bail early if there are only four or less words
        if ($wordCount < 4) {
            return $string;
        }

        $lastWord = Support::decode($words[$wordCount - 1]);
        $secondLastWord = Support::decode($words[$wordCount - 2]);

        // Return unchanged if the last two words together are longer then 25 characters
        if (mb_strlen("$lastWord $secondLastWord") > 25) {
            return $string;
        }

        $result = preg_replace('/([^\s])\s+([^\s]+)\s*$/', '$1&nbsp;$2', $string);

        return $result ?? $string;
    }


}
