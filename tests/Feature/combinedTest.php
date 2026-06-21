<?php

use Hirasso\HTMLProcessor\Support\Support;

use function Hirasso\HTMLProcessor\process;

test('Runs various tasks on a string', function () {
    $html = trimLines(<<<HTML
    <p></p><div></div>
    <p><!-- preserve-me --></p>
    <p>Please reach out to <a href="mailto:mail@example.com">mail@example.com</a> to learn more.</p>
    <p>Follow @acme on SocialWeb.</p>
    HTML);

    $result = process($html)
        ->autolinkUrls() // wrap raw url strings in `<a>` tags
        ->processLinks(fn ($link) => $link->addClasses()->openExternalInNewTab())
        ->autolinkPrefix('@', 'https://your-instance.social/@') // link @profileName to Mastodon
        ->autolinkPrefix('#', 'https://your-instance.social/tags') // link #hashTag to Mastodon
        ->removeEmptyElements('p,div') // remove empty paragraphs
        ->obfuscateEmail()
        ->apply();

    // Email encoding is randomized, so check for specific patterns instead of exact match
    expect($result)->not->toContain('<p></p>');
    expect($result)->not->toContain('<div></div>');
    expect($result)->not->toContain('<p><!-- preserve me --></p>');
    expect($result)->toContain('class="link--mailto"');
    expect($result)->toContain('href="https://your-instance.social/@acme">@acme</a>');
    expect($result)->not->toContain('&lt;'); // HTML tags should not be escaped
    expect($result)->not->toContain('&amp;nbsp;'); // Entities should not be double-encoded
    expect($result)->toContain('href="liam/moc.elpmaxe"'); // email should be obfuscated via data attribute
    expect($result)->not->toContain('href="mailto:'); // original mailto href should be gone
});


test('Runs autolinkUrls before processLinks', function () {
    $html = trimLines(<<<HTML
    <p>https://example.com</p>
    HTML);

    $expected = trimLines(<<<HTML
    <p><a href="https://example.com" class="link--internal">example.com</a></p>
    HTML);

    $result = process($html)
        ->processLinks()
        ->autolinkUrls()
        ->apply();

    expect($result)->toBe($expected);
});

test('Runs autolinkUrls before obfuscateEmail', function () {
    $html = trimLines(<<<HTML
    <p>mail@example.com</p>
    HTML);

    $expected = trimLines(<<<HTML
    <p><a href="mailto:mail@example.com">mail@example.com</a></p>
    HTML);

    $result = process($html)
        ->obfuscateEmail()
        ->autolinkUrls()
        ->apply();

    // autolinkUrls (prio -10) runs before obfuscateEmail (prio 0), so the naked
    // email gets linked first and then link-conversion obfuscates the resulting <a>
    expect($result)->toContain('href="liam/moc.elpmaxe"');
    expect($result)->not->toContain('href="mailto:');
});

test('apply() returns empty string unchanged when html is empty', function () {
    expect(process('')->stripTags()->apply())->toBe('');
});

test('apply() returns original html when no mutations are queued', function () {
    expect(process('<p>foo</p>')->apply())->toBe('<p>foo</p>');
});

test('__toString() returns the same result as apply()', function () {
    $processor = process('<p>foo</p>')->stripTags();
    expect((string) $processor)->toBe($processor->apply());
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
        process($html)
            ->removeEmptyElements('p')
            ->apply()
    )->toBe($expected);

    /** compare to native Dom\HTMLDocument */
    $doc = \Dom\HTMLDocument::createFromString($html, LIBXML_NOERROR);
    expect(Support::extractBodyHTML($doc))->toBe($expected);
});
