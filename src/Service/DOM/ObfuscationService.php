<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM;

use Dom\Element;
use Dom\HTMLDocument;
use Dom\Text;
use Hirasso\HTMLProcessor\Service\Contract\DOMServiceContract;
use Hirasso\HTMLProcessor\Support\Support;
use Override;
use RuntimeException;

/**
 * Obfuscate email addresses in HTML to protect them from spam bots.
 *
 * @see https://spencermortensen.com/articles/email-obfuscation/
 */
final class ObfuscationService implements DOMServiceContract
{
    #[Override]
    public function prio(): int
    {
        return 10;
    }

    private readonly string $keyPrefix;
    private const string EMAIL_REGEX = "[^\s@]+@[^\s@]+\.[^\s@]{2,}";
    private const string PHONE_NUMBER_REGEX = "[\+\d][\d \-\(\)\.]{6,20}(?<!\s)";

    public function __construct(
        string $keyPrefix = 'html-processor-obfuscation',
        public bool $processEmails = false,
        public bool $processPhoneNumbers = false
    ) {
        $this->keyPrefix = md5($keyPrefix);
    }

    #[Override]
    public function run(HTMLDocument $document): void
    {
        $this->obfuscateLinks($document);

        if ($this->processEmails) {
            foreach (Support::getTextNodes($document) as $node) {
                $this->obfuscateTextNode($node, self::EMAIL_REGEX);
            }
        }
        if ($this->processPhoneNumbers) {
            foreach (Support::getTextNodes($document) as $node) {
                $this->obfuscateTextNode($node, self::PHONE_NUMBER_REGEX);
            }
        }

    }

    /**
     * Obfuscate links
     */
    private function obfuscateLinks(HTMLDocument $document): void
    {
        if ($this->processEmails) {
            foreach ($document->querySelectorAll('a[href*="mailto:"]') as $link) {
                $email = substr($link->getAttribute('href') ?? '', strlen('mailto:'));
                if (!preg_match('/^' . self::EMAIL_REGEX . '$/', $email)) {
                    continue;
                }
                $link->replaceWith($this->obfuscateElement($link));
            }
        }
        if ($this->processPhoneNumbers) {
            foreach ($document->querySelectorAll('a[href*="tel:"]') as $link) {
                $tel = substr($link->getAttribute('href') ?? '', strlen('tel:'));
                if (!preg_match('/^' . self::PHONE_NUMBER_REGEX . '$/', $tel)) {
                    continue;
                }
                $link->replaceWith($this->obfuscateElement($link));
            }
        }
    }

    /**
     * Process a whole element
     */
    private function obfuscateElement(Element $el): Element
    {
        if (!$el->ownerDocument) {
            throw new RuntimeException('No owner document found');
        }
        $obfuscated = $el->ownerDocument->createElement('html-processor-obfuscated');
        $obfuscated->setAttribute('key', $this->getKey());
        $obfuscated->setAttribute('value', $this->encode(Support::outerHTML($el)));
        $obfuscated->setAttribute('type', 'element');

        return $obfuscated;
    }

    /**
     * Process all matches within a text node
     */
    private function obfuscateTextNode(Text $node, string $regex): void
    {
        $node->data = preg_replace_callback(
            "/{$regex}/",
            fn ($matches) => $this->obfuscate($matches[0]),
            $node->data
        ) ?? '';

        Support::hydrateTextNode($node);
    }

    /**
     * Obfuscate a string
     */
    private function obfuscate(string $value): string
    {
        $encoded = $this->encode($value);
        $key = $this->getKey();

        return sprintf(
            <<<HTML
            <html-processor-obfuscated value="%s" key="%s">foobar</html-processor-obfuscated>
            HTML,
            $encoded,
            $key
        );
    }

    /**
     * Get the key, unique per request
     */
    private function getKey(): string
    {
        static $key;

        $key ??= uniqid($this->keyPrefix, true);

        return $key;
    }

    /**
     * Encode a string, using a secret key
     */
    private function encode(string $data): string
    {
        $key = $this->getKey();
        $out = '';
        for ($i = 0; $i < strlen($data); $i++) {
            $out .= $data[$i] ^ $key[$i % strlen($key)];
        }
        return base64_encode($out);
    }
}
