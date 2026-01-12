<?php

use Hirasso\HTMLProcessor\Enum\UrlType;
use Hirasso\HTMLProcessor\HTMLProcessor;

test('Runs various tasks on a string', function () {
    $html = trimLines(<<<HTML
    <p></p><!-- delete me -->
    <p>Please reach out to <a href="mailto:mail@example.com">mail@example.com</a> to learn more.</p>
    <p>Follow @acme on SocialWeb.</p>
    <p>And some more text that should not have a widow</p>
    HTML);

    $result = HTMLProcessor::fromString($html)
        ->autolinkUrls() // wrap raw url strings in `<a>` tags
        ->typography( // optimize typography
            'de_DE',
            localizeQuotes: true, // format quotes based on locale
            preventWidows: true // prevent widows
        )
        ->processLinks(
            function ($el, $type) { // process links by callback
                if ($type === UrlType::External) {
                    $el->setAttribute('target', '_blank');
                }
            },
            addClasses: true // automatically add classes by type (mailto:, tel, internal, external, ...)
        )
        ->autolinkPrefix('@', 'https://your-instance.social/@') // link @profileName to Mastodon
        ->autolinkPrefix('#', 'https://your-instance.social/tags') // link #hashTag to Mastodon
        ->removeEmptyElements('p') // remove empty paragraphs
        ->encodeEmails()
        ->process();

    // Email encoding is randomized, so check for specific patterns instead of exact match
    expect($result)->toContain('<!-- delete me -->');
    expect($result)->toContain('class="link--mailto"');
    expect($result)->toContain('href="https://your-instance.social/@acme">@acme</a>');
    expect($result)->toContain('&nbsp;');
    expect($result)->not->toContain('&lt;'); // HTML tags should not be escaped
    expect($result)->not->toContain('&amp;nbsp;'); // Entities should not be double-encoded
    expect($result)->toMatch('/&#[0-9]+;|&#x[0-9a-fA-F]+;/'); // Should contain encoded email entities
    expect($result)->toContain('a&nbsp;widow'); // widow prevented
});
