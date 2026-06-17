<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\HTML;

use Hirasso\HTMLProcessor\Service\Contract\HTMLServiceContract;

/**
 * Encodes email addresses found in the HTML to make it a little harder for bots
 */
final readonly class ObfuscateContacts implements HTMLServiceContract
{
    public function __construct(
        private bool $email = true,
        private bool $phone = true
    ) {

    }
    public function prio(): int
    {
        return 0;
    }


    public function run(string $html): string
    {
        $html = $this->encodeEmails($html);
        $html = $this->encodePhoneNumbers($html);

        return $html;
    }

    /**
     * Find email addresses and encode them
     *
     * Regular expression is based on based on John Gruber's Markdown.
     * @see http://daringfireball.net/projects/markdown/
     */
    private function encodeEmails(string $html): string
    {
        if (!$this->email) {
            return $html;
        }

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

        $result = preg_replace_callback(
            $pattern,
            fn ($matches) => $this->encodeString($matches[0]),
            $html
        );

        return $result ?? $html;
    }

    /**
     * Find phone numbers and encode them
     */
    private function encodePhoneNumbers(string $html): string
    {
        if (!$this->phone) {
            return $html;
        }

        if (!str_contains($html, 'tel:')) {
            return $html;
        }

        $result = preg_replace_callback(
            '/<a(\s[^>]*?)>(.*?)<\/a>/is',
            function ($matches) {
                $attrs = $matches[1];
                $text = $matches[2];

                if (!preg_match('/href=["\']\s?tel:/i', $attrs)) {
                    return $matches[0];
                }

                $attrs = preg_replace_callback(
                    '/href=((["\'])tel:[^"\']+\2)/i',
                    fn ($m) => 'href=' . $this->encodeString($m[1]),
                    $attrs
                );

                $text = preg_replace_callback(
                    '/[\d+]/',
                    fn ($m) => $this->encodeString($m[0]),
                    $text
                );

                return '<a' . $attrs . '>' . $text . '</a>';
            },
            $html
        );

        return $result ?? $html;
    }

    /**
     * Encodes each character of the given string as either a decimal
     * or hexadecimal entity, in the hopes of foiling most email address
     * harvesting bots.
     *
     * Based on Michel Fortin's PHP Markdown:
     * @see http://michelf.com/projects/php-markdown/
     * Which is based on John Gruber's original Markdown:
     * @see http://daringfireball.net/projects/markdown/
     * Whose code is based on a filter by Matthew Wickline, posted to
     * the BBEdit-Talk with some optimizations by Milian Wolff.
     */
    private function encodeString(string $string): string
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
