<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM\Typography;

use DOMXPath;
use Hirasso\HTMLProcessor\Service\Contract\DOMServiceContract;
use Hirasso\HTMLProcessor\Support\Helpers;
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
        $textNodes = $xPath->query('//text()');

        if ($textNodes === false) {
            return;
        }

        /**
         * Process each text node that is a direct child of a block element
         */
        foreach ($textNodes as $node) {
            $parentNode = $node->parentNode;

            if ($parentNode === null) {
                continue;
            }

            $parentTagName = strtolower($parentNode->nodeName);

            if (in_array($parentTagName, self::BLOCK_ELEMENTS, true)) {
                $node->textContent = $this->maybePreventWidows($node->textContent);
            }
        }
    }

    /**
     * Prevent widows in a string
     *
     * @see http://davidwalsh.name/prevent-widows-php-javascript
     */
    private function maybePreventWidows(string $textContent): string
    {
        // first remove any eventual white space
        $string = Helpers::normalizeWhitespace($textContent);

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

        $lastWord = $this->placeholdersToEntities($words[$wordCount - 1]);
        $secondLastWord = $this->placeholdersToEntities($words[$wordCount - 2]);

        // bail early if the last two words together are longer then 25 characters
        if (strlen("$lastWord $secondLastWord") > 25) {
            return $string;
        }

        $result = preg_replace('/([^\s])\s+([^\s]+)\s*$/', '$1&nbsp;$2', $string);

        return $result ?? $string;
    }

    /**
     * Convert internal entity placeholders from HTML5DOMDocument back to the real entity
     */
    private function placeholdersToEntities(string $html): string
    {
        if (strpos($html, 'html5-dom-document-internal-entity') === false) {
            return $html;
        }
        $html = preg_replace('/html5-dom-document-internal-entity1-(.*?)-end/', '&$1;', $html) ?? $html;
        $html = preg_replace('/html5-dom-document-internal-entity2-(.*?)-end/', '&#$1;', $html) ?? $html;
        return $html;
    }
}
