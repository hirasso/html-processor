<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM\Quotes;

/**
 * Replaces standard ASCII quotes with localized typographic quotes.
 *
 * Uses a two-phase approach:
 * 1. Normalize: Convert any existing curly quotes back to ASCII (' and ")
 * 2. Localize: Use stack-based matching to assign open/close roles, then replace
 *
 * The stack-based approach handles nested same-type quotes correctly:
 * Input:  "foo "outer 'inner' outer" bar"
 * Output: "foo "outer 'inner' outer" bar"  (L L L R R R pattern)
 */
final readonly class QuoteReplacer
{
    private QuoteFinder $finder;

    public function __construct(
        public string $lang,
        public QuotePair $single,
        public QuotePair $double
    ) {
        $this->finder = new QuoteFinder();
    }

    /**
     * Apply this replacer to a string
     */
    public function applyTo(string $text): string
    {
        // Phase 1: Normalize existing curly quotes to standard ASCII quotes
        $text = $this->normalize($text, SingleQuote::all(), "'");
        $text = $this->normalize($text, DoubleQuote::all(), '"');

        // Phase 2: Apply stack-based localization
        $text = $this->localizeWithStack($text);

        return $text;
    }

    /**
     * Normalize curly quotes back to ASCII, but only if they look like quotes
     * (not preceded by letter OR not followed by letter).
     * This preserves apostrophes like "don't" which are surrounded by letters.
     *
     * @param string[] $quotes Curly quote characters to normalize
     */
    private function normalize(string $text, array $quotes, string $replace): string
    {
        $matchBefore = '(?<!\p{L})'; // negative lookbehind: not preceded by letter
        $matchAfter = '(?!\p{L})';   // negative lookahead: not followed by letter

        // Build character class like [''‚‹›]
        $class = '[' . preg_quote(implode('', $quotes), '/') . ']';

        // Match if NOT preceded by letter OR NOT followed by letter
        // This catches opening quotes (space before) and closing quotes (space after)
        $pattern = "/(?:{$matchBefore}{$class}|{$class}{$matchAfter})/u";

        return preg_replace($pattern, $replace, $text) ?? $text;
    }

    /**
     * Localize quotes using stack-based matching for proper nesting.
     *
     * Why stack-based? A greedy regex like "(.*)" matches from first to last quote,
     * breaking nested structures. The stack tracks unclosed quotes, enabling correct
     * pairing even with nesting: "foo "bar" baz" → L L R R (not L-----R)
     */
    private function localizeWithStack(string $text): string
    {
        // Step 1: Find all quote positions with context (can open? can close?)
        $quotes = $this->finder->find($text);

        // Step 2: Walk through quotes and assign Open/Close roles using a stack
        $this->assignRoles($quotes);

        // Step 3: Replace from end to start to preserve byte positions
        // (replacing from start would shift positions of subsequent quotes)
        for ($i = count($quotes) - 1; $i >= 0; $i--) {
            $match = $quotes[$i];
            if ($match->role === null) {
                continue; // Unmatched quote, leave as-is
            }

            $pair = $match->type === QuoteType::Single ? $this->single : $this->double;
            $replacement = $match->role === QuoteRole::Open ? $pair->opening : $pair->closing;

            $text = substr_replace($text, $replacement, $match->position, $match->length);
        }

        return $text;
    }

    /**
     * Assign Open/Close roles to quotes using stack counters.
     *
     * Algorithm:
     * - Walk left to right through all quotes
     * - If quote can close AND there's an unclosed quote of same type → Close (pop)
     * - Else if quote can open → Open (push)
     * - Else → unmatched, role stays null
     *
     * @param QuoteMatch[] $quotes
     */
    private function assignRoles(array $quotes): void
    {
        $singleStack = 0;
        $doubleStack = 0;

        foreach ($quotes as $match) {
            $isSingle = $match->type === QuoteType::Single;
            $stack = $isSingle ? $singleStack : $doubleStack;

            if ($match->canClose && $stack > 0) {
                $match->role = QuoteRole::Close;
                if ($isSingle) {
                    $singleStack--;
                } else {
                    $doubleStack--;
                }
            } elseif ($match->canOpen) {
                $match->role = QuoteRole::Open;
                if ($isSingle) {
                    $singleStack++;
                } else {
                    $doubleStack++;
                }
            }
        }
    }

}
