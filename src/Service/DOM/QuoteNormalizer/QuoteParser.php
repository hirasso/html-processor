<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM\QuoteNormalizer;

/**
 * Parses text into segments for quote wrapping.
 *
 * Uses stack-based matching for proper nesting:
 * Input:  "outer "inner" outer"
 * Output: [QuoteOpen, Text("outer "), QuoteOpen, Text("inner"), QuoteClose, Text(" outer"), QuoteClose]
 */
final class QuoteParser
{
    /**
     * Parse text into segments.
     *
     * @return Segment[]
     */
    public function parse(string $text): array
    {
        // Phase 1: Reset existing curly quotes to standard ASCII "
        $text = $this->resetQuotes($text);

        // Phase 2: Find candidates for quotes
        $candidates = $this->findCandidates($text);

        // Phase 2: Find all quotes and assign roles (only paired quotes returned)
        $quotes = $this->findPairedQuotes($candidates);

        // Phase 3: Build and return segments
        return $this->buildSegments($text, $quotes);
    }

    /**
     * Reset curly quotes back to ASCII ", but only if they look like quotes
     * (not preceded by letter OR not followed by letter).
     * This preserves apostrophes like "don't" which are surrounded by letters.
     */
    private function resetQuotes(string $text): string
    {
        $matchBefore = '(?<!\p{L})';
        $matchAfter = '(?!\p{L})';

        $class = '[' . preg_quote(implode('', CurlyQuotes::all()), '/') . ']';
        $pattern = "/(?:{$matchBefore}{$class}|{$class}{$matchAfter})/u";

        return preg_replace($pattern, '"', $text) ?? $text;
    }

    /**
     * Find paired quotes using a stack
     *
     * @param QuoteCandidate[] $candidates
     * @return Quote[]
     */
    private function findPairedQuotes(array $candidates): array
    {
        /** @var QuoteCandidate[] $stack */
        $stack = [];
        /** @var Quote[] $paired */
        $paired = [];

        foreach ($candidates as $candidate) {
            if ($candidate->canClose && count($stack) > 0) {
                $opener = array_pop($stack);
                $paired[] = new Quote($opener->position, QuoteRole::Open);
                $paired[] = new Quote($candidate->position, QuoteRole::Close);
            } elseif ($candidate->canOpen) {
                $stack[] = $candidate;
            }
        }

        // Sort by position since openers are added when paired
        usort($paired, static fn ($a, $b) => $a->position <=> $b->position);

        return $paired;
    }

    /**
     * @param Quote[] $quotes Paired quotes only (all have roles assigned)
     * @return Segment[]
     */
    private function buildSegments(string $text, array $quotes): array
    {
        if (empty($text)) {
            return [];
        }

        if (empty($quotes)) {
            return [new TextSegment($text)];
        }

        $segments = [];
        $cursor = 0;

        foreach ($quotes as $match) {
            if ($match->position > $cursor) {
                $segments[] = new TextSegment(substr($text, $cursor, $match->position - $cursor));
            }

            $segments[] = new QuoteSegment($match->role);

            $cursor = $match->position + 1;
        }

        if ($cursor < strlen($text)) {
            $segments[] = new TextSegment(substr($text, $cursor));
        }

        return $segments;
    }

    /**
     * Find all double quotes with their context.
     *
     * @return QuoteCandidate[]
     */
    private function findCandidates(string $text): array
    {
        if (!preg_match_all('/"/', $text, $matches, PREG_OFFSET_CAPTURE)) {
            return [];
        }
        /** we are only interested in the match positions */
        $positions = array_map(
            static fn ($match) => (int) $match[1],
            $matches[0]
        );

        return array_map(
            fn ($position) => new QuoteCandidate(
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
