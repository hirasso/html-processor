<?php

use function Hirasso\HTMLProcessor\process;

test('Links @mentions to Instagram', function () {
    $result = process("<p>@hirasso</p>")->autolinkPrefix('@', 'https://www.instagram.com/')->apply();
    expect($result)->toBe('<p><a href="https://www.instagram.com/hirasso">@hirasso</a></p>');
});

test('Links #tags to Instagram', function () {
    $result = process("#tag")->autolinkPrefix('#', 'https://www.instagram.com/explore/tags/')->apply();
    expect($result)->toBe('<a href="https://www.instagram.com/explore/tags/tag">#tag</a>');
});

test('Links @mentions to custom url', function () {
    $result = process("@hirasso")->autolinkPrefix('@', 'https://your-instance.social/@')->apply();
    expect($result)->toBe('<a href="https://your-instance.social/@hirasso">@hirasso</a>');
});

test('Links #tags to custom url', function () {
    $result = process("#php")->autolinkPrefix('#', 'https://your-instance.social/tags')->apply();
    expect($result)->toBe('<a href="https://your-instance.social/tags/php">#php</a>');
});

test('Works in complex HTML', function () {
    $html = <<<HTML
    <p></p><!-- delete me -->
    <p>Please reach out to <a href="mailto:mail@example.com">mail@example.com</a> to learn more.</p>
    <p>Follow @acme on SocialWeb.</p>
    <p>And some more text that should not have a widow</p>
    HTML;

    $result = process($html)
        ->autolinkPrefix('@', 'https://your-instance.social/@')
        ->apply();

    expect($result)->toBe(<<<HTML
    <p></p><!-- delete me -->
    <p>Please reach out to <a href="mailto:mail@example.com">mail@example.com</a> to learn more.</p>
    <p>Follow <a href="https://your-instance.social/@acme">@acme</a> on SocialWeb.</p>
    <p>And some more text that should not have a widow</p>
    HTML);
});

test('Does not re-link text already inside an anchor', function () {
    $html = '<p><a href="https://example.com">@mention</a></p>';
    $result = process($html)->autolinkPrefix('@', 'https://social.example.com/@')->apply();
    expect($result)->toBe('<p><a href="https://example.com">@mention</a></p>');
    expect($result)->not->toContain('<a href="https://social.example.com/@mention">');
});

test('Works with repeated calls', function () {
    $html = trimLines(<<<HTML
        <p>Follow @acme on SocialWeb.</p>
        <p>Learn more about #php on SocialWeb.</p>
    HTML);

    $expected = trimLines(<<<HTML
        <p>Follow <a href="https://your-instance.social/@acme">@acme</a> on SocialWeb.</p>
        <p>Learn more about <a href="https://your-instance.social/tags/php">#php</a> on SocialWeb.</p>
    HTML);

    $result = process($html)
        ->autolinkPrefix('@', 'https://your-instance.social/@')
        ->autolinkPrefix('#', 'https://your-instance.social/tags')
        ->apply();

    expect($result)->toBe($expected);
});
