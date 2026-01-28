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
     * Find all quote candidates
     *
     * @return QuoteCandidate[]
     */
    private function findCandidates(string $text): array
    {
        /**
         * Convert the CurlyQuotes to a character class
         */
        $quotePattern = '[' . preg_quote(implode('', CurlyQuotes::all()), '/') . ']';

        /**
         * (?<!\S)
         *  - Negative lookbehind
         *  - Asserts that the previous character is NOT a non-whitespace character
         *  - This is equivalent to:
         *      • start of string (^)
         *      • OR preceded by whitespace (\s)
         */
        $startOrWhitespace = '(?<!\S)';

        /**
         * (?!\S)
         *  - Negative lookahead
         *  - Asserts that the next character is NOT a non-whitespace character
         *  - This is equivalent to:
         *      • end of string ($)
         *      • OR followed by whitespace (\s)
         */
        $endOrWhitespace = '(?!\S)';

        /**
         * Lookahead and behind for any letter
         */
        $anyLetterBefore = '(?<=\p{L})';
        $anyLetterAfter = '(?=\p{L})';

        $candidates = [];

        $canOpen = $this->matchAll("/{$startOrWhitespace}{$quotePattern}{$anyLetterAfter}/u", $text);
        $canClose = $this->matchAll("/{$anyLetterBefore}{$quotePattern}{$endOrWhitespace}/u", $text);
        $canOpenAndClose = $this->matchAll("/{$startOrWhitespace}{$quotePattern}{$endOrWhitespace}/", $text);

        foreach( $canOpen as $match) {
            $candidates[] = new QuoteCandidate(
                position: $match->position,
                canOpen: true,
                canClose: false,
            );
        }

        foreach( $canClose as $match) {
            $candidates[] = new QuoteCandidate(
                position: $match->position,
                canOpen: false,
                canClose: true,
            );
        }

        foreach( $canOpenAndClose as $match) {
            $candidates[] = new QuoteCandidate(
                position: $match->position,
                canOpen: true,
                canClose: true,
            );
        }

        return $candidates;
    }

    /** @return object{matched: string, position: int}[] */
    private function matchAll(string $pattern, string $text): array
    {
        dump(compact('pattern', 'text'));

        preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);

        if (empty($matches[0])) {
            return [];
        }

        $matches = array_map(
            fn ($match) => (object) ['matched' => $match[0], 'position' => (int) $match[1]],
            $matches[0]
        );

        return $matches;
    }
}
