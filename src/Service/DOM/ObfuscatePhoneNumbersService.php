<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM;

use Dom\HTMLDocument;
use Dom\Text;
use Hirasso\HTMLProcessor\Service\Contract\DOMServiceContract;
use Hirasso\HTMLProcessor\Support\Support;
use Override;

/**
 * Obfuscate email addresses in HTML to protect them from spam bots.
 *
 * @see https://spencermortensen.com/articles/email-obfuscation/
 */
final readonly class ObfuscatePhoneNumbersService implements DOMServiceContract
{
    #[Override]
    public function prio(): int
    {
        return 10;
    }

    private const string TEL_REGEX = "[\+\d][\d\s\-\(\)\.]{6,20}";

    #[Override]
    public function run(HTMLDocument $document): void
    {
        $this->obfuscateLinks($document);

        foreach (Support::getTextNodes($document) as $node) {
            $this->obfuscateTextNode($node, $document);
        }
    }

    /**
     * Convert <a href="mailto:...">
     *
     * @see https://spencermortensen.com/articles/email-obfuscation/#link-conversion
     */
    private function obfuscateLinks(HTMLDocument $document): void
    {
        foreach ($document->querySelectorAll('a[href^="tel:"]') as $link) {
            $tel = substr($link->getAttribute('href') ?? '', strlen('tel:'));
            if (!preg_match('/^' . self::TEL_REGEX . '$/', $tel)) {
                continue;
            }

            $link->setAttribute('href', $this->encode($tel));
            $link->setAttribute('rel', 'nofollow noindex');
            $script = $document->createElement('script');
            $script->textContent = Support::trimWhitespace(<<<'JS'
            (function () {
                const el = document.currentScript.closest("a");
                const decode = (tel) => tel.split("/").reverse().join("");
                el.setAttribute("href", "tel:" + decode(el.getAttribute("href")));
                el.removeAttribute("rel");
                document.currentScript.remove();
            })();
            JS);
            $link->prepend($script);
        }
    }

    /**
     * Text-conversion: naked email in text nodes → inline JS
     *
     * @see https://spencermortensen.com/articles/email-obfuscation/#text-conversion
     */
    private function obfuscateTextNode(Text $node, HTMLDocument $document): void
    {
        $obfuscated = preg_replace_callback(
            pattern: '/' . self::TEL_REGEX . '/',
            callback: function ($matches) {
                $encoded = $this->encode($matches[0]);
                return <<<HTML
                <body><script>document.currentScript.replaceWith("$encoded".split("/").reverse().join(""))</script></body>
                HTML;
            },
            subject: $node->data
        ) ?? '';

        if ($parsed = Support::parseHtml($obfuscated)) {
            $node->replaceWith($document->importNode($parsed, deep: true));
        }
    }

    /**
     * Encode a telephone number
     * - split in two parts
     * - reverse the parts and wrap with '/'
     */
    private function encode(string $tel): string
    {
        $splitAt = intdiv(strlen($tel), 2);
        $a = substr($tel, 0, $splitAt);
        $b = substr($tel, $splitAt);
        return "/{$b}/{$a}/";
    }
}
