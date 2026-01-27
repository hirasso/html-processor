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
        $quotes = [];

        if (!preg_match_all('/"/', $text, $matches, PREG_OFFSET_CAPTURE)) {
            return $quotes;
        }

        foreach ($matches[0] as [, $pos]) {
            $position = (int) $pos;

            $quotes[] = new QuoteMatch(
                position: $position,
                canOpen: !$this->isPrecededByLetter($text, $position),
                canClose: !$this->isFollowedByLetter($text, $position + 1),
            );
        }

        return $quotes;
    }

    private function isPrecededByLetter(string $text, int $position): bool
    {
        if ($position <= 0) {
            return false;
        }

        $before = mb_substr(substr($text, 0, $position), -1, 1);

        return preg_match('/\p{L}/u', $before) === 1;
    }

    private function isFollowedByLetter(string $text, int $position): bool
    {
        if ($position >= strlen($text)) {
            return false;
        }

        $after = mb_substr(substr($text, $position), 0, 1);

        return preg_match('/\p{L}/u', $after) === 1;
    }
}
