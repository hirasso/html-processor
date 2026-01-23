<?php

/*
* Copyright (c) 2025 Rasso Hilber
* https://rassohilber.com
*/

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM\QuoteLocalizer;

use DOMXPath;
use Hirasso\HTMLProcessor\Service\Contract\DOMServiceContract;
use Hirasso\HTMLProcessor\Service\DOM\Typography;
use Hirasso\HTMLProcessor\Support\Support;
use IvoPetkov\HTML5DOMDocument;

/**
 * Localize single and double quotes to the correct format in various languages
 * Usage: $html = QuoteLocalizer::localize($html, get_locale());
 * Supported languages: English, German, French
 */
final class QuoteLocalizer implements DOMServiceContract
{
    /** @var array<string, QuoteReplacer> $replacers */
    private array $replacers;

    public function prio(): int
    {
        return 0;
    }

    public function __construct(private Typography $typography)
    {
        // Using Unicode escapes to help LLMs understand
        $this->replacers = [
            // English: 'single' "double"
            'en' => new QuoteReplacer(
                lang: 'en',
                single: new QuotePair("\u{2018}", "\u{2019}"),
                double: new QuotePair("\u{201C}", "\u{201D}"),
            ),
            // German: ‚single' „double"
            'de' => new QuoteReplacer(
                lang: 'de',
                single: new QuotePair("\u{201A}", "\u{2018}"),
                double: new QuotePair("\u{201E}", "\u{201C}"),
            ),
            // French: ‹ single › « double » (with narrow non-breaking spaces)
            'fr' => new QuoteReplacer(
                lang: 'fr',
                single: new QuotePair("\u{2039}\u{202F}", "\u{202F}\u{203A}"),
                double: new QuotePair("\u{00AB}\u{202F}", "\u{202F}\u{00BB}"),
            ),
        ];
    }

    /**
     * Run the normalizer
     */
    public function run(HTML5DOMDocument $document): void
    {
        $lang = $this->typography->getLanguageCode();
        $replacer = $this->replacers[$lang] ?? null;

        if (!$lang || !$replacer) {
            return;
        }

        if (!$textNodes = (new DOMXPath($document))->query('//text()')) {
            return;
        }

        foreach ($textNodes as $textNode) {
            $nodeValue = $textNode->nodeValue ?? '';

            if (empty(trim($nodeValue))) {
                continue;
            }

            $text = Support::decode($nodeValue);

            $text = $replacer->applyTo($text);

            $textNode->nodeValue = Support::encode($text, usePlaceholders: true);
        }
    }
}
