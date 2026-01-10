<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor;

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
}
