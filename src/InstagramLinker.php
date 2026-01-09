<?php

/*
 * Copyright (c) Rasso Hilber
 * https://rassohilber.com
 */

declare(strict_types=1);

namespace Hirasso\HTMLProcessor;

use IvoPetkov\HTML5DOMDocument;
use DOMXPath;

final class InstagramLinker
{

    public static function link(HTML5DOMDocument $document): void {
        $xpath = new DOMXPath($document);
        $textNodes = $xpath->query('//text()');

        foreach ($textNodes as $textNode) {
            // Skip text nodes inside <a> elements
            if ($xpath->query('ancestor::a', $textNode)->length === 0) {
                $textNode->nodeValue = self::processTextNode($textNode->nodeValue);
            }
        }
    }

    protected static function processTextNode(string $text): string
    {
        if (empty(trim($text))) {
            return $text;
        }

        /** Convert @handle_name to https://www.instagram.com/handle_name */
        $text = preg_replace_callback('/(?<=^|\s)@(?<user>.*?)(?=\s|$)/', function ($matches) {
            $baseURL = 'https://www.instagram.com';
            $user = $matches['user'];
            return "<a href=\"$baseURL/$user\">@{$user}</a>";
        }, $text);

        /** Convert #hashtag to instgram keyword URLs */
        $text = preg_replace_callback('/(?<=^|\s)#(?<hashtag>.*?)(?=\s|$)/', function ($matches) {
            $baseURL = 'https://www.instagram.com/explore/tags';
            $hashtag = $matches['hashtag'];
            return "<a href=\"{$baseURL}/{$hashtag}\">#{$hashtag}</a>";
        }, $text);

        return $text;
    }
}
