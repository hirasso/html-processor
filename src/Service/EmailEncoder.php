<?php

/*
* Copyright (c) 2025 Rasso Hilber
* https://rassohilber.com
*/

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service;

/**
 * Encodes email addresses found in the HTML to make it a little harder for bots
 */
final readonly class EmailEncoder
{
    /**
     * Searches for plain email addresses in given $string and encodes them
     *
     * Regular expression is based on based on John Gruber's Markdown.
     * http://daringfireball.net/projects/markdown/
     */
    public function encode(string $html): string
    {
        if (!str_contains($html, '@')) {
            return $html;
        }

        $pattern = '{
            (?:mailto:)?
            (?:
                [-!#$%&*+/=?^_`.{|}~\w\x80-\xFF]+
            |
                ".*?"
            )
            \@
            (?:
                [-a-z0-9\x80-\xFF]+(\.[-a-z0-9\x80-\xFF]+)*\.[a-z]+
            |
                \[[\d.a-fA-F:]+\]
            )
        }xi';

        return preg_replace_callback(
            $pattern,
            fn ($matches) => self::encodeString($matches[0]),
            $html
        );
    }

    /**
     * Encodes each character of the given string as either a decimal
     * or hexadecimal entity, in the hopes of foiling most email address
     * harvesting bots.
     *
     * Based on Michel Fortin's PHP Markdown:
     *   http://michelf.com/projects/php-markdown/
     * Which is based on John Gruber's original Markdown:
     *   http://daringfireball.net/projects/markdown/
     * Whose code is based on a filter by Matthew Wickline, posted to
     * the BBEdit-Talk with some optimizations by Milian Wolff.
     */
    protected static function encodeString(string $string): string
    {
        $chars = str_split($string);
        $seed = mt_rand(0, (int) abs(crc32($string) / strlen($string)));

        foreach ($chars as $key => $char) {
            $ord = ord($char);

            if ($ord < 128) { // ignore non-ascii chars
                $r = ($seed * (1 + $key)) % 100; // pseudo "random function"

                if ($r > 60 && $char !== '@' && $char !== '.') {
                    // plain character (not encoded), except @-signs and dots
                } elseif ($r < 45) {
                    $chars[$key] = '&#x' . dechex($ord) . ';'; // hexadecimal
                } else {
                    $chars[$key] = '&#' . $ord . ';'; // decimal (ascii)
                }
            }
        }
        $encoded = implode('', $chars);

        return $encoded;
    }
}
