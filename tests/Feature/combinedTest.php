<?php

use Hirasso\HTMLProcessor\Enum\UrlType;
use Hirasso\HTMLProcessor\HTMLProcessor;

test('Runs various tasks on a string', function () {
    $html = <<<HTML
    <p></p><!-- delete me -->
    <p>Please reach out to <a href="mailto:mail@example.com">mail@example.com</a> to learn more.</p>
    <p>Follow @acme on SocialWeb.</p>
    <p>And some more text that should not have a widow</p>
    HTML;

    $expected = <<<HTML
    <!-- delete me -->
    <p>Please reach out to <a href="mailto:mail@example.com" class="link--mailto">mail@example.com</a> to learn more, or follow @acme on SocialWeb.</p>
    <p>Follow <a href="https://your-instance.social/@acme">@acme</a> on SocialWeb.</p>
    <p>And some more text that should not have a&nbsp;widow</p>
    HTML;

    $result = HTMLProcessor::fromString($html)
        // ->autolinkUrls() // wrap raw url strings in `<a>` tags
        // ->typography( // optimize typography
        //     'de_DE',
        //     localizeQuotes: true, // format quotes based on locale
        //     preventWidows: true // prevent widows
        // )
        // ->processLinks(
        //     function ($el, $type) { // process links by callback
        //         if ($type === UrlType::External) {
        //             $el->setAttribute('target', '_blank');
        //         }
        //     },
        //     addClasses: true // automatically add classes by type (mailto:, tel, internal, external, ...)
        // )
        ->autolinkPrefix('@', 'https://your-instance.social/@') // link @profileName to Mastodon
        ->autolinkPrefix('#', 'https://your-instance.social/tags') // link #hashTag to Mastodon
        // ->removeEmptyElements('p') // remove empty paragraphs
        ->process();

    expect($result)->toBe($expected);
});
