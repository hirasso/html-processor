<?php

/*
 * Copyright (c) Rasso Hilber
 * https://rassohilber.com
 */

declare(strict_types=1);

namespace Hirasso\HTMLProcessor;

final class Beautifier
{
    public function __construct(protected HTMLProcessor $processor)
    {
    }

    /**
     * Remove empty-looking paragraphs from html
     */
    public function removeEmptyParagraphs(): self
    {
        foreach ($this->processor->queryAll('p') as $p) {

            $textContent = $this->removeWhitespace($p->textContent);

            if (empty($textContent)) {
                $p->parentNode->removeChild($p);
            }
        }

        return $this;
    }

    /**
     * Remove any whitespace-looking stuff from a html string
     * \s matches regular whitespace, \xc2\xa0 matches UTF-8 non-breaking space
     * @see https://stackoverflow.com/a/30101404/586823
     */
    protected function removeWhitespace(string $string): string
    {
        $string = trim(str_replace("html5-dom-document-internal-entity1-nbsp-end", " ", $string));
        $string = preg_replace('/^[\s\xc2\xa0]*$/i', '', $string);
        $string = preg_replace('/^[\s\xc2\xa0]*&nbsp;[\s\xc2\xa0]*$/i', '', $string);
        return $string;
    }

    /**
     * Prevent widows in html text
     */
    public function preventWidows()
    {
        $textNodes = $this->processor->queryXPath('//text()');

        /**
         * Traverse the DOMNodeList backwards, prevent widows on the first textNode that is not empty
         */
        for ($i = $textNodes->length; $i--; $i >= 0) {
            $node = $textNodes[$i];
            if (empty(trim($node->textContent))) {
                continue;
            }
            $node->textContent = $this->maybePreventWidows($node->textContent);
            break;
        }
    }

    /**
     * Prevent widows in a string
     *
     * @see http://davidwalsh.name/prevent-widows-php-javascript
     */
    private function maybePreventWidows(string $string): string
    {
        // first remove any eventual white space
        $string = $this->removeWhitespace($string);

        // count the words
        $words = explode(" ", $string);
        $wordCount = count($words);

        // bail early if there are only four or less words
        if ($wordCount < 4) {
            return $string;
        }

        $lastWord = self::placeholdersToEntities($words[$wordCount - 1]);
        $secondLastWord = self::placeholdersToEntities($words[$wordCount - 2]);

        // bail early if the last two words together are longer then 25 characters
        if (strlen("$lastWord $secondLastWord") > 25) {
            return $string;
        }

        $string = preg_replace('/([^\s])\s+([^\s]+)\s*$/', '$1&nbsp;$2', $string);

        return $string;
    }

    /**
     * Convert internal entity placeholders from HTML5DOMDocument back to the real entity
     */
    protected static function placeholdersToEntities(string $html): string
    {
        if (strpos($html, 'html5-dom-document-internal-entity') === false) {
            return $html;
        }
        $html = preg_replace('/html5-dom-document-internal-entity1-(.*?)-end/', '&$1;', $html);
        $html = preg_replace('/html5-dom-document-internal-entity2-(.*?)-end/', '&#$1;', $html);
        return $html;
    }
}
