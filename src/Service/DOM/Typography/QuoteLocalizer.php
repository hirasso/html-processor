<?php

/*
* Copyright (c) 2025 Rasso Hilber
* https://rassohilber.com
*/

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM\Typography;

use DOMXPath;
use Hirasso\HTMLProcessor\Service\Contract\DOMServiceContract;
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
        $this->replacers = [
            'en' => new QuoteReplacer(
                lang: 'en',
                single: fn (string $s) => "‘{$s}’",
                double: fn (string $s) => "“{$s}”"
            ),
            'de' => new QuoteReplacer(
                lang: 'de',
                single: fn (string $s) => "‚{$s}‘",
                double: fn (string $s) => "„{$s}“"
            ),
            // French has narrow non-breaking spaces between the quotes and the word
            'fr' => new QuoteReplacer(
                lang: 'de',
                single: fn (string $s) => "‹\u{202F}{$s}\u{202F}›",
                double: fn (string $s) => "«\u{202F}{$s}\u{202F}»"
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
