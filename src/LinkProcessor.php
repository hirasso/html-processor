<?php

/*
 * Copyright (c) Rasso Hilber
 * https://rassohilber.com
 */

declare(strict_types=1);

namespace Hirasso\HTMLProcessor;

use IvoPetkov\HTML5DOMDocument;
use IvoPetkov\HTML5DOMElement;

enum UrlType
{
    case Internal;
    case External;
    case Invalid;
}

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
final class LinkProcessor
{
    public static function process(
        HTML5DOMDocument $document,
        ?callable $callback = null,
    ): void {
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

            if ($linkClass = match(true) {
                str_starts_with($href, 'mailto:') => 'link--mailto',
                str_starts_with($href, 'tel:') => 'link--tel',
                str_starts_with($href, '#') => 'link--anchor',
                $urlType === UrlType::External => 'link--external',
                $urlType === UrlType::Internal => 'link--internal',
                default => null
            }) {
                $classList[] = $linkClass;
            }

            if ($urlType === UrlType::External) {
                $el->setAttribute('target', '_blank');
            }

            if (self::isFileLink($href)) {
                $classList[] = 'link--file';
                // Add extension-specific class
                $ext = strtolower(pathinfo(parse_url($href, PHP_URL_PATH), PATHINFO_EXTENSION));
                $classList[] = "link--file-{$ext}";
            }

            $el->setAttribute('class', implode(' ', $classList));

            if ($callback !== null) {
                $callback($el);
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
            $parsed === false => UrlType::Invalid,
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
     * Check if a URL points to a file
     */
    protected static function isFileLink(string $url): bool
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);

        // Exclude non-http(s) schemes
        if ($scheme && !in_array($scheme, ['http', 'https', ''])) {
            return false;
        }

        $path = parse_url($url, PHP_URL_PATH);

        if ($path === null || $path === '' || $path === '/') {
            return false;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        // If no extension or common web extensions, it's likely not a file
        $webExtensions = ['html', 'htm', 'php', 'asp', 'aspx', 'jsp'];

        return $extension !== '' && !in_array($extension, $webExtensions);
    }

}
