<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Internal\Service\DOM\ShortLastLineAvoider;

use DOMNode;
use DOMText;
use DOMXPath;
use Hirasso\HTMLProcessor\Internal\Service\Contract\DOMServiceContract;
use Hirasso\HTMLProcessor\Internal\Support\Support;
use IvoPetkov\HTML5DOMDocument;
use IvoPetkov\HTML5DOMElement;

final readonly class ShortLastLineAvoider implements DOMServiceContract
{
    public function __construct(
        private ?int $minWordCount = 4,
        private ?int $maxTailLength = 25
    ) {

    }

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
        $blockElements = $xPath->query(BlockElement::xPathSelector());

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
        $selector = BlockElement::xPathSelector('.//');
        $childBlocks = $xPath->query($selector, $element);

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
            if ($this->isValidCandidate($child)) {
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

    /** @phpstan-assert-if-true DOMText $el */
    private function isValidCandidate(DOMNode $el): bool
    {
        if (!($el instanceof DOMText)) {
            return false;
        }
        if (empty(trim($el->textContent))) {
            return false;
        }
        return !$this->isInExcludedElement($el);
    }

    /** @param ?array<array-key, string> $excluded */
    private function isInExcludedElement(DOMText $text, ?array $excluded = null): bool
    {
        $excluded ??= ['head', 'link', 'pre', 'code', 'script', 'style'];
        for ($node = $text->parentNode; $node !== null; $node = $node->parentNode) {
            if ($node instanceof HTML5DOMElement) {
                if (in_array(strtolower($node->tagName), $excluded, true)) {
                    return true;
                }
            }
        }

        return false;
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

        if ($wordCount < $this->minWordCount) {
            return $string;
        }

        $lastWord = Support::decode($words[$wordCount - 1]);
        $secondLastWord = Support::decode($words[$wordCount - 2]);

        if (mb_strlen("$lastWord $secondLastWord") > $this->maxTailLength) {
            return $string;
        }

        $lastWord = array_pop($words);

        return implode(' ', $words) . '&nbsp;' . $lastWord;
    }
}
