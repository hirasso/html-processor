<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM;

use Closure;
use Hirasso\HTMLProcessor\Enum\UrlType;
use Hirasso\HTMLProcessor\Service\Contract\DOMServiceContract;
use IvoPetkov\HTML5DOMDocument;
use IvoPetkov\HTML5DOMElement;

/**
 * Process links in HTML. Add classes reflecting the behaviour
 *
 * - mailto:
 * - tel:
 * - #hash
 * - internal
 * - external
 * - to files
 */
final readonly class LinkProcessor implements DOMServiceContract
{
    /** @param ?Closure(\IvoPetkov\HTML5DOMElement): mixed $callback */
    public function __construct(
        protected ?Closure $callback = null,
    ) {
    }

    public function run(HTML5DOMDocument $document): void
    {
        foreach ($document->querySelectorAll('a[href]') as $el) {

            /** @var HTML5DOMElement $el */
            $href = trim($el->getAttribute('href'));

            if (empty($href)) {
                continue;
            }

            $classAttr = $el->getAttribute('class');
            $classList = $classAttr ? explode(' ', $classAttr) : [];

            if (str_starts_with($el->textContent, 'http')) {
                $classList[] = 'link--contains-http';
            }

            $urlType = self::detectUrlType($href);

            $classList[] = match(true) {
                str_starts_with($href, 'mailto:') => 'link--mailto',
                str_starts_with($href, 'tel:') => 'link--tel',
                str_starts_with($href, '#') => 'link--anchor',
                $urlType === UrlType::External => 'link--external',
                $urlType === UrlType::Internal => 'link--internal',
                default => 'link--unknown'
            };

            if ($urlType === UrlType::External) {
                $el->setAttribute('target', '_blank');
            }

            if ($extension = self::getFileLinkExtension($href)) {
                $classList[] = "link--file";
                $classList[] = "link--file--{$extension}";
            }

            $classList = array_filter(
                array_map('trim', $classList),
                fn($class) => !empty(trim($class))
            );

            $el->setAttribute('class', implode(' ', $classList));

            if ($this->callback !== null) {
                ($this->callback)($el);
            }
        }
    }

    /**
     * Detect the type of a URL (internal/external/invalid)
     */
    protected static function detectUrlType(string $url): UrlType
    {
        $baseDomain = $_SERVER['HTTP_HOST'] ?? '';

        $parsed = parse_url($url);

        return match(true) {
            !$parsed => UrlType::Invalid,
            !isset($parsed['host']) => UrlType::Internal,
            default => (function () use ($parsed, $baseDomain): UrlType {
                $host = strtolower($parsed['host']);
                $base = strtolower($baseDomain);

                // Strip www. from both for comparison
                $hostNormalized = preg_replace('/^www\./', '', $host);
                $baseNormalized = preg_replace('/^www\./', '', $base);

                // Only match exact domains (with or without www.)
                return ($hostNormalized === $baseNormalized)
                    ? UrlType::Internal
                    : UrlType::External;
            })()
        };
    }

    /**
     * Check if a URL points to a file. If so, return the extension.
     * Ignore "web" extensions like ".html", ".php" ect.
     */
    protected static function getFileLinkExtension(string $url): ?string
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);

        // Exclude non-http(s) schemes
        if ($scheme && !in_array($scheme, ['http', 'https', ''])) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH) ?: '';

        if (in_array($path, ['', '/'], true)) {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        // If no extension or common web extensions, it's likely not a file
        $webExtensions = ['html', 'htm', 'php', 'asp', 'aspx', 'jsp'];

        return $extension && !in_array($extension, $webExtensions, true)
            ? $extension
            : null;
    }

}
