<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Internal\Service\DOM\LinkProcessor;

use Exception;
use Hirasso\HTMLProcessor\Internal\Enum\LinkType;
use IvoPetkov\HTML5DOMElement;
use League\Uri\Uri;

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

        if (!$linkUri = $this->getUri($url)) {
            return LinkType::Invalid;
        }

        $scheme = $linkUri->getScheme();

        $isCustomScheme = $scheme && !in_array($scheme, ['http', 'https'], true);
        $isAnchorToCurrentPage = str_starts_with(trim($url), '#');

        return match(true) {
            $scheme === 'mailto' => LinkType::Mailto,
            $scheme === 'tel' => LinkType::Tel,
            $isCustomScheme => LinkType::External,
            $isAnchorToCurrentPage => LinkType::Anchor,
            !$linkUri->getHost() => LinkType::Internal,
            default => (function () use ($linkUri): LinkType {
                $currentUri = $this->getUri('//' . ($_SERVER['HTTP_HOST'] ?? ''));

                $linkHostname = $this->removeWWW($linkUri->getHost());
                $currentHostname = $this->removeWWW($currentUri?->getHost());

                // Only match exact domains (with or without www.)
                return (!$currentHostname || $linkHostname  === $currentHostname)
                    ? LinkType::Internal
                    : LinkType::External;
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

    /**
     * Apply classes with a customizable prefix
     */
    public function addClasses(?string $prefix = null): self
    {
        $prefix ??= 'link';

        $this->el->classList->add("{$prefix}--{$this->type->value}");

        if ($this->isLinkToFile()) {
            $this->el->classList->add("{$prefix}--file");
        }

        if ($this->extension) {
            $this->el->classList->add("{$prefix}--ext--$this->extension");
        }

        return $this;
    }

    /**
     * Open external links in a new tab by adding [target="_blank"]
     * @param ?bool $safe add [rel="noopener noreferrer"] to links
     */
    public function openExternalInNewTab(?bool $safe = true): self
    {
        if ($this->type !== LinkType::External) {
            return $this;
        }

        $this->el->setAttribute('target', '_blank');

        if ($safe) {
            $this->el->setAttribute('rel', "noopener noreferrer");
        }

        return $this;
    }
}
