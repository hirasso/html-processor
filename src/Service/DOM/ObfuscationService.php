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
        public bool $obfuscateEmails = false,
        public bool $obfuscatePhoneNumbers = false
    ) {
        $this->keyPrefix = md5($keyPrefix);
    }

    #[Override]
    public function run(HTMLDocument $document): void
    {
        // $this->obfuscateLinks($document);

        if ($this->obfuscateEmails) {
            foreach (Support::getTextNodes($document) as $node) {
                $this->processTextNode($node, self::EMAIL_REGEX);
            }
        }
        if ($this->obfuscatePhoneNumbers) {
            foreach (Support::getTextNodes($document) as $node) {
                $this->processTextNode($node, self::PHONE_NUMBER_REGEX);
            }
        }

    }

    /**
     * Convert <a href="mailto:...">
     *
     * @see https://spencermortensen.com/articles/email-obfuscation/#link-conversion
     */
    private function obfuscateLinks(HTMLDocument $document): void
    {
        if ($this->obfuscateEmails) {
            foreach ($document->querySelectorAll('a[href*="mailto:"]') as $link) {
                $email = substr($link->getAttribute('href') ?? '', strlen('mailto:'));
                if (!preg_match('/^' . self::EMAIL_REGEX . '$/', $email)) {
                    continue;
                }

                $link->removeAttribute('href');
                $link->setAttribute('data-html-processor', $this->encode($email));
            }
        }
        if ($this->obfuscatePhoneNumbers) {
            foreach ($document->querySelectorAll('a[href*="tel:"]') as $link) {
                $tel = substr($link->getAttribute('href') ?? '', strlen('tel:'));
                if (!preg_match('/^' . self::PHONE_NUMBER_REGEX . '$/', $tel)) {
                    continue;
                }

                $link->removeAttribute('href');
                $link->setAttribute('data-html-processor', $this->encode($tel));
            }
        }
    }

    /**
     * Obfuscate all matches within a text node
     */
    private function processTextNode(Text $node, string $regex): void
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
