<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM\QuoteLocalizer;

/**
 * Finds quote characters in text and determines their open/close context.
 *
 * Context rules:
 * - canOpen: true if NOT preceded by a letter
 * - canClose: true if NOT followed by a letter
 * - Apostrophes like "don't" have both false → ignored
 */
final class QuoteFinder
{
    /**
     * Find all quotes matching the pattern with their context.
     *
     * @param string $text Text to search
     * @param string $pattern Regex pattern to match quotes (default: ASCII ' and ")
     * @return QuoteMatch[]
     */
    public function find(string $text, string $pattern = '/[\'"]/'): array
    {
        $quotes = [];

        if (!preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
            return $quotes;
        }

        foreach ($matches[0] as [$char, $pos]) {
            $position = (int) $pos;
            $type = $char === "'" ? QuoteType::Single : QuoteType::Double;
            $length = strlen($char);

            $quotes[] = new QuoteMatch(
                position: $position,
                length: $length,
                type: $type,
                canOpen: !$this->isPrecededByLetter($text, $position),
                canClose: !$this->isFollowedByLetter($text, $position + $length),
            );
        }

        return $quotes;
    }

    /**
     * Check if position is preceded by a Unicode letter.
     */
    private function isPrecededByLetter(string $text, int $position): bool
    {
        if ($position <= 0) {
            return false;
        }

        $before = mb_substr(substr($text, 0, $position), -1, 1);

        return preg_match('/\p{L}/u', $before) === 1;
    }

    /**
     * Check if position is followed by a Unicode letter.
     */
    private function isFollowedByLetter(string $text, int $position): bool
    {
        if ($position >= strlen($text)) {
            return false;
        }

        $after = mb_substr(substr($text, $position), 0, 1);

        return preg_match('/\p{L}/u', $after) === 1;
    }
}
