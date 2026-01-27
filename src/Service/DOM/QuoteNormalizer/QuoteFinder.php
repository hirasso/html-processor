<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM\QuoteNormalizer;

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
     * Find all double quotes with their context.
     *
     * @return QuoteMatch[]
     */
    public function find(string $text): array
    {
        if (!preg_match_all('/"/', $text, $matches, PREG_OFFSET_CAPTURE)) {
            return [];
        }
        /** we are only interested in the match positions */
        $positions = array_map(
            fn ($match) => (int) $match[1],
            $matches[0]
        );

        return array_map(
            fn ($position) => new QuoteMatch(
                position: $position,
                canOpen: !$this->isPrecededByLetter($text, $position),
                canClose: !$this->isFollowedByLetter($text, $position + 1),
            ),
            $positions
        );
    }

    private function isPrecededByLetter(string $text, int $bytePos): bool
    {
        if ($bytePos <= 0) {
            return false;
        }

        $charBefore = mb_substr(substr($text, 0, $bytePos), -1, 1);

        return preg_match('/\p{L}/u', $charBefore) === 1;
    }

    private function isFollowedByLetter(string $text, int $bytePos): bool
    {
        if ($bytePos >= strlen($text)) {
            return false;
        }

        $charAfter = mb_substr(substr($text, $bytePos), 0, 1);

        return preg_match('/\p{L}/u', $charAfter) === 1;
    }
}
