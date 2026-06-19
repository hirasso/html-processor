<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Uri;

use Dom\Element;

/**
 * @phpstan-type ParsedUri array{
 *   scheme?: string,
 *   host?: string,
 *   port?: int,
 *   user?: string,
 *   pass?: string,
 *   path?: string,
 *   query?: string,
 *   fragment?: string
 * }
 */
final readonly class Uri
{
    /** @var ParsedUri|null */
    public ?array $parsed;

    public string $uri;

    private function __construct(
        string $uri
    ) {
        $this->uri = trim($uri);

        $this->parsed = $this->parse($uri);
    }

    /**
     * Parse and normalize
     * @return ParsedUri|null
     */
    private function parse(string $uri): ?array
    {
        if (!$parsed = parse_url($uri)) {
            return null;
        }

        // Normalize scheme and host
        foreach (['scheme', 'host'] as $part) {
            if ($parsed[$part] ?? null) {
                $parsed[$part] = strtolower($parsed[$part]);
            }
        }

        return $parsed;
    }

    public static function fromString(string $str): self
    {
        return new self($str);
    }

    public static function fromElement(Element $el): self
    {
        return self::fromString($el->getAttribute('href') ?? '');
    }

    public function isInvalid(): bool
    {
        return $this->parsed === null;
    }

    public function getScheme(): ?string
    {
        return $this->parsed['scheme'] ?? null;
    }

    public function getHost(): ?string
    {
        return $this->parsed['host'] ?? null;
    }

    public function getFragment(): ?string
    {
        return $this->parsed['fragment'] ?? null;
    }

    /**
     * Get the domain without www.
     */
    public function getDomain(): ?string
    {
        return preg_replace('/^www\./', '', $this->getHost() ?? '') ?? null;
    }

    public function getPath(): ?string
    {
        return $this->parsed['path'] ?? null;
    }

    /**
     * Check if this link has a file extension
     */
    public function getExtension(): ?string
    {
        $scheme = $this->getScheme();

        // Exclude non-http(s) schemes
        if ($scheme && !in_array($scheme, ['http', 'https', ''])) {
            return null;
        }

        $path = $this->getPath();

        if (in_array($path, [null, '', '/'], true)) {
            return null;
        }

        return strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }

    /**
     * Check if the domain of this uri is equal to the current domain
     */
    public function isCurrentDomain(): bool
    {
        $currentBaseDomain = new self('//' . ($_SERVER['HTTP_HOST'] ?? ''))->getDomain();
        return !$currentBaseDomain || $this->getDomain() === $currentBaseDomain;
    }

    /**
     * Check if a URL points to a file. If so, return the extension.
     * Ignore "web" extensions like ".html", ".php" ect.
     */
    public function pointsToFile(): bool
    {
        if (!$extension = $this->getExtension()) {
            return false;
        }
        $webExtensions = ['html', 'htm', 'php', 'asp', 'aspx', 'jsp'];
        return !in_array($extension, $webExtensions, true);
    }

    /**
     * Get the type of a URL (internal/external/invalid)
     */
    public function getType(): UriType
    {
        if ($this->isInvalid()) {
            return UriType::Invalid;
        }

        $scheme = $this->getScheme();

        $isCustomScheme = $scheme && !in_array($scheme, ['http', 'https'], true);

        return match(true) {
            $scheme === 'mailto' => UriType::Mailto,

            $scheme === 'tel' => UriType::Tel,

            $isCustomScheme => UriType::External,

            !$this->getHost() => UriType::Internal,

            default => $this->isCurrentDomain() ? UriType::Internal : UriType::External,
        };
    }


}
