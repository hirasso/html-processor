<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Support;

use IvoPetkov\HTML5DOMDocument;

final class Helpers
{
    /**
     * Convert entities, while preserving already-encoded entities.
     *
     * @link https://www.php.net/htmlentities Borrowed from the PHP Manual user notes.
     */
    public static function htmlentities(string $text): string
    {
        $translation_table = get_html_translation_table(HTML_ENTITIES, ENT_QUOTES);
        $translation_table[chr(38)] = '&';

        return preg_replace(
            '/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,3};)/',
            '&amp;',
            strtr($text, $translation_table)
        );
    }

    /**
     * Extract HTML from body
     */
    public static function extractBodyHTML(HTML5DOMDocument $document): string
    {
        $html = $document->saveHTML();
        preg_match('/<body[^>]*>(?<content>.*?)<\/body>/is', $html, $matches);
        $html = $matches['content'] ?? '';
        $html = str_replace('="__BOOLEAN_TRUE__"', '', $html);
        return $html;
    }

    /**
     * Remove any whitespace-looking stuff from a html string
     * \s matches regular whitespace, \xc2\xa0 matches UTF-8 non-breaking space
     * @see https://stackoverflow.com/a/30101404/586823
     */
    public static function normalizeWhitespace(string $string): string
    {
        $string = str_replace("html5-dom-document-internal-entity1-nbsp-end", ' ', $string);
        $string = preg_replace('/^[\s\xc2\xa0]*$/i', ' ', $string);
        $string = preg_replace('/^[\s\xc2\xa0]*&nbsp;[\s\xc2\xa0]*$/i', ' ', $string);
        $string = preg_replace('/\s+/', ' ', $string);
        return $string;
    }
}
