<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM\ShortLastLineAvoider;

use DOMNode;
use DOMText;
use DOMXPath;
use Hirasso\HTMLProcessor\Service\Contract\DOMServiceContract;
use Hirasso\HTMLProcessor\Support\Support;
use IvoPetkov\HTML5DOMDocument;

final readonly class ShortLastLineAvoider implements DOMServiceContract
{
    public function prio(): int
    {
        return 0;
    }

    /**
     * Prevent short last lines (widows) in html text
     */
    public function run(HTML5DOMDocument $document): void
    {
        $xPath = new DOMXPath($document);
        $blockElements = $xPath->query(BlockElement::xPathQuery());

        if ($blockElements === false) {
            return;
        }

        foreach ($blockElements as $el) {
            if (!($el instanceof DOMNode)) {
                continue;
            }

            if ($this->containsBlockElements($el, $xPath)) {
                continue;
            }

            if (!$lastTextNode = $this->findLastTextNode($el)) {
                continue;
            };


            $lastTextNode->textContent = $this->injectNonbreakingSpace($lastTextNode->textContent);

        }
    }

    /**
     * Check if an element contains other block elements
     */
    private function containsBlockElements(DOMNode $element, DOMXPath $xPath): bool
    {
        $childBlocks = $xPath->query(BlockElement::xPathQuery('.//'), $element);

        return $childBlocks !== false && $childBlocks->length > 0;
    }

    /**
     * Find the last non-empty text node within an element
     */
    private function findLastTextNode(DOMNode $node): ?DOMText
    {
        if (!$node->hasChildNodes()) {
            return null;
        }

        $children = array_reverse(iterator_to_array($node->childNodes));

        foreach ($children as $child) {
            if ($child instanceof DOMText && !empty(trim($child->textContent))) {
                return $child;
            }

            if ($child->hasChildNodes()) {
                $lastTextNode = $this->findLastTextNode($child);
                if ($lastTextNode !== null) {
                    return $lastTextNode;
                }
            }
        }

        return null;
    }

    /**
     * Prevent short last lines (widows) in a string by injecting a
     * non-breaking space between the last two words
     *
     * @see http://davidwalsh.name/prevent-widows-php-javascript
     */
    private function injectNonbreakingSpace(string $textContent): string
    {
        $string = Support::normalizeWhitespace($textContent);

        if (empty(trim($string))) {
            return $textContent;
        }

        $words = explode(' ', $string);
        $wordCount = count($words);

        if ($wordCount < 4) {
            return $string;
        }

        $lastWord = Support::decode($words[$wordCount - 1]);
        $secondLastWord = Support::decode($words[$wordCount - 2]);

        if (mb_strlen("$lastWord $secondLastWord") > 25) {
            return $string;
        }

        return preg_replace('/([^\s])\s+([^\s]+)\s*$/', '$1&nbsp;$2', $string) ?? $string;
    }
}
