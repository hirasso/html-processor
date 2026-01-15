<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM\LinkProcessor;

use Hirasso\HTMLProcessor\Enum\LinkType;
use IvoPetkov\HTML5DOMElement;

final readonly class Link
{
    public string $href;
    public LinkType $type;
    public ?string $extension;

    public function __construct(
        public HTML5DOMElement $el
    ) {
        $this->href = trim($el->getAttribute('href'));
        $this->type = $this->getType($this->href);
        $this->extension = $this->getExtension($this->href);
    }

    /**
     * Get the type of a URL (internal/external/invalid)
     */
    private function getType(?string $url): LinkType
    {
        if (is_null($url)) {
            return LinkType::Invalid;
        }

        $baseDomain = $_SERVER['HTTP_HOST'] ?? '';

        $parsed = parse_url($url);
        $host = strtolower($parsed['host'] ?? '');
        $scheme = strtolower($parsed['scheme'] ?? '');
        $isCustomScheme = !!$scheme && !in_array($scheme, ['http', 'https'], true);
        $isAnchorToCurrentPage = str_starts_with(trim($url), '#');

        return match(true) {
            !$parsed => LinkType::Invalid,
            $scheme === 'mailto' => LinkType::Mailto,
            $scheme === 'tel' => LinkType::Tel,
            $isCustomScheme => LinkType::External,
            $isAnchorToCurrentPage => LinkType::Anchor,
            !$host => LinkType::Internal,
            default => (function () use ($parsed, $baseDomain): LinkType {
                $host = strtolower($parsed['host']);
                $base = strtolower($baseDomain);

                // Strip www. from both for comparison
                $hostNormalized = preg_replace('/^www\./', '', $host);
                $baseNormalized = preg_replace('/^www\./', '', $base);

                // Only match exact domains (with or without www.)
                return ($hostNormalized === $baseNormalized)
                    ? LinkType::Internal
                    : LinkType::External;
            })()
        };
    }

    /**
     * Check if a URL points to a file. If so, return the extension.
     * Ignore "web" extensions like ".html", ".php" ect.
     */
    private function getExtension(string $url): ?string
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

        return strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }

    /**
     * Check if an this link has a file extension
     */
    public function isLinkToFile(): bool
    {
        if (!$this->extension) {
            return false;
        }
        $webExtensions = ['html', 'htm', 'php', 'asp', 'aspx', 'jsp'];
        return !in_array($this->extension, $webExtensions, true);
    }
}
