<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Internal\Service\DOM\QuoteNormalizer;

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
        // Phase 1: Find candidates for quotes
        $candidates = $this->findCandidates($text);

        // Phase 2: Find all quotes and assign roles (only paired quotes returned)
        $quotes = $this->findPairedQuotes($candidates);

        // Phase 3: Build and return segments
        return $this->buildSegments($text, $quotes);
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
                $paired[] = new Quote(QuoteRole::Open, $opener->position, $opener->char);
                $paired[] = new Quote(QuoteRole::Close, $candidate->position, $candidate->char);
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

            // Advance cursor by the quote's byte length (UTF-8 curly quotes are 3 bytes)
            $cursor = $match->position + strlen($match->char);
        }

        if ($cursor < strlen($text)) {
            $segments[] = new TextSegment(substr($text, $cursor));
        }

        return $segments;
    }

    /**
     * Find all quote candidates
     *
     * @return QuoteCandidate[]
     */
    private function findCandidates(string $text): array
    {
        return [
            ...$this->matchesToCandidates($text, QuoteRole::Open),
            ...$this->matchesToCandidates($text, QuoteRole::Close),
            ...$this->matchesToCandidates($text),
        ];
    }

    /** @return QuoteCandidate[] */
    private function matchesToCandidates(string $string, ?QuoteRole $role = null): array
    {
        /**
         * Convert the CurlyQuotes to a character class
         */
        $quotePattern = '[' . preg_quote(implode('', CurlyQuotes::all()), '/') . ']';

        /**
         * (?<![\p{L}\p{N}])
         *  - Negative lookbehind
         *  - Asserts that the previous character is NOT a letter or number
         *  - Allows punctuation like ( ) [ ] etc. before quotes
         */
        $notAfterAlphanumeric = '(?<![\p{L}\p{N}])';

        /**
         * (?![\p{L}\p{N}])
         *  - Negative lookahead
         *  - Asserts that the next character is NOT a letter or number
         *  - Allows punctuation like ( ) [ ] etc. after quotes
         */
        $notBeforeAlphanumeric = '(?![\p{L}\p{N}])';

        /**
         * Lookahead and behind for any letter
         */
        $anyLetterBefore = '(?<=\p{L})';
        $anyLetterAfter = '(?=\p{L})';

        $pattern = match($role) {
            QuoteRole::Open => "/{$notAfterAlphanumeric}{$quotePattern}{$anyLetterAfter}/u",
            QuoteRole::Close => "/{$anyLetterBefore}{$quotePattern}{$notBeforeAlphanumeric}/u",
            /**
             * If no quote role was provided it could be opening as well as closing
             * @TODO: Maybe we don't ever need this
             */
            default => "/{$notAfterAlphanumeric}{$quotePattern}{$notBeforeAlphanumeric}/u"
        };

        preg_match_all($pattern, $string, $matches, PREG_OFFSET_CAPTURE);

        if (empty($matches[0])) {
            return [];
        }

        return array_map(
            fn ($match) => new QuoteCandidate(
                char: $match[0],
                position: (int) $match[1],
                canOpen: $role === QuoteRole::Open,
                canClose: $role === QuoteRole::Close
            ),
            $matches[0]
        );
    }
}
