<?php

use Hirasso\HTMLProcessor\HTMLProcessor;
use Hirasso\HTMLProcessor\Support\Helpers;

test('Runs various tasks on a string', function () {
    $html = trimLines(<<<HTML
    <p></p><div></div>
    <p><!-- preserve-me --></p>
    <p>Please reach out to <a href="mailto:mail@example.com">mail@example.com</a> to learn more.</p>
    <p>Follow @acme on SocialWeb.</p>
    <p>And some more text that should not have a widow</p>
    HTML);

    $result = HTMLProcessor::fromString($html)
        ->autolinkUrls() // wrap raw url strings in `<a>` tags
        ->typography(fn ($typo) => $typo
                ->setLocale('de_DE')
                ->localizeQuotes()
                ->preventWidows())
        ->processLinks(function ($link, $defaultHandler) { // process links by callback
            if ($link->type->value === 'external') {
                $link->el->setAttribute('target', '_blank');
            }
            $defaultHandler(); // run the default handler (adds classes based on type)
        })
        ->autolinkPrefix('@', 'https://your-instance.social/@') // link @profileName to Mastodon
        ->autolinkPrefix('#', 'https://your-instance.social/tags') // link #hashTag to Mastodon
        ->removeEmptyElements('p,div') // remove empty paragraphs
        ->encodeEmails()
        ->apply();

    // Email encoding is randomized, so check for specific patterns instead of exact match
    expect($result)->not->toContain('<p></p>');
    expect($result)->not->toContain('<div></div>');
    expect($result)->not->toContain('<p><!-- preserve me --></p>');
    expect($result)->toContain('class="link--mailto"');
    expect($result)->toContain('href="https://your-instance.social/@acme">@acme</a>');
    expect($result)->toContain('&nbsp;');
    expect($result)->not->toContain('&lt;'); // HTML tags should not be escaped
    expect($result)->not->toContain('&amp;nbsp;'); // Entities should not be double-encoded
    expect($result)->toMatch('/&#[0-9]+;|&#x[0-9a-fA-F]+;/'); // Should contain encoded email entities
    expect($result)->toContain('a&nbsp;widow'); // widow prevented
});


test('Runs autolinkUrls before processLinks', function () {
    $html = trimLines(<<<HTML
    <p>https://example.com</p>
    HTML);

    $expected = trimLines(<<<HTML
    <p><a href="https://example.com" class="link--external">example.com</a></p>
    HTML);

    $result = HTMLProcessor::fromString($html)
        ->processLinks()
        ->autolinkUrls()
        ->apply();

    expect($result)->toBe($expected);
});

test('Runs autolinkUrls before encodeEmails', function () {
    $html = trimLines(<<<HTML
    <p>mail@example.com</p>
    HTML);

    $expected = trimLines(<<<HTML
    <p><a href="mailto:mail@example.com">mail@example.com</a></p>
    HTML);

    $result = HTMLProcessor::fromString($html)
        ->encodeEmails()
        ->autolinkUrls()
        ->apply();

    expect($result)->toMatch('/&#[0-9]+;|&#x[0-9a-fA-F]+;/'); // Should contain encoded email entities
    expect(html_entity_decode($result))->toBe($expected);
});

test('Works with self-closing tags', function () {
    $html = <<<HTML
    <p>
        Foo<br />
        bar
    </p>
    HTML;

    // DOM processing normalizes HTML: <br /> → <br>, &amp; → &
    $expected = <<<HTML
    <p>
        Foo<br>
        bar
    </p>
    HTML;

    expect(
        HTMLProcessor::fromString($html)
            ->removeEmptyElements()
            ->apply()
    )->toBe($expected);

    /** compare  to native DOMDocument */
    $doc = new \DOMDocument();
    $doc->loadHTML($html);
    expect(Helpers::extractBodyHTML($doc))->toBe($expected);
});
