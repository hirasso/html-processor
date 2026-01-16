<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM\LinkProcessor;

use Exception;
use Hirasso\HTMLProcessor\Enum\UrlType;
use IvoPetkov\HTML5DOMElement;
use League\Uri\Uri;

final readonly class Link
{
    public string $href;
    public UrlType $type;
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
    private function getType(?string $url): UrlType
    {
        if (is_null($url)) {
            return UrlType::Invalid;
        }

        if (!$linkUri = $this->getUri($url)) {
            return UrlType::Invalid;
        }

        $scheme = $linkUri->getScheme();

        $isCustomScheme = $scheme && !in_array($scheme, ['http', 'https'], true);
        $isAnchorToCurrentPage = str_starts_with(trim($url), '#');

        return match(true) {
            $scheme === 'mailto' => UrlType::Mailto,
            $scheme === 'tel' => UrlType::Tel,
            $isCustomScheme => UrlType::External,
            $isAnchorToCurrentPage => UrlType::Anchor,
            !$linkUri->getHost() => UrlType::Internal,
            default => (function () use ($linkUri): UrlType {
                $currentUri = $this->getUri('//' . ($_SERVER['HTTP_HOST'] ?? ''));

                $linkHostname = $this->removeWWW($this->getHostname($linkUri));
                $currentHostname = $this->removeWWW($this->getHostname($currentUri));

                // Only match exact domains (with or without www.)
                return (!$currentHostname || $linkHostname  === $currentHostname)
                    ? UrlType::Internal
                    : UrlType::External;
            })()
        };
    }

    private function getUri(string $uri): ?Uri
    {
        try {
            return Uri::new($uri);
        } catch (Exception $e) {
            return null;
        }
    }

    private function removeWWW(?string $uri = null): ?string
    {
        return preg_replace('/^www\./', '', $uri ?? '') ?? $uri;
    }

    private function getHostname(?Uri $uri = null): ?string
    {
        if (!$uri) {
            return null;
        }

        $hostname = $uri->getHost();

        if (!$port = $uri->getPort()) {
            return $hostname;
        }

        return "$hostname:$port";
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
