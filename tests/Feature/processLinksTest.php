<?php

use function Hirasso\HTMLProcessor\process;

beforeEach(function () {
    // spoof the current (internal) URL to make checks for `isCurrentDomain` work as expected
    $_SERVER['HTTP_HOST'] = 'internal.example.com';
});

afterEach(function () {
    $_SERVER['HTTP_HOST'] = null;
});

test('Processes mailto: links', function () {
    $result = process('<a href="mailto:mail@internal.example.com"></a>')->processLinks();
    expect($result->apply())->toBe('<a href="mailto:mail@internal.example.com" class="link--mailto"></a>');
});

test('Treats custom schemes as external', function () {
    $result = process('<a href="skype://internal.example.com"></a>')->processLinks();
    expect($result->apply())->toBe('<a href="skype://internal.example.com" class="link--external"></a>');
});

test('Processes tel: links', function () {
    $result = process('<a href="tel:123456"></a>')->processLinks();
    expect($result->apply())->toBe('<a href="tel:123456" class="link--tel"></a>');
});

test('Processes #anchor links', function () {
    $result = process('<a href="#some-anchor"></a>')->processLinks();
    expect($result->apply())->toBe('<a href="#some-anchor" class="link--internal link--anchor"></a>');

    $result = process('<a href="https://external.example.com/#some-anchor"></a>')->processLinks();
    expect($result->apply())->toBe('<a href="https://external.example.com/#some-anchor" class="link--external link--anchor"></a>');
});

test('Processes internal links', function () {
    $result = process('<a href="https://internal.example.com">internal.example.com</a>')->processLinks();
    expect($result->apply())->toBe('<a href="https://internal.example.com" class="link--internal">internal.example.com</a>');
});

test('Processes local file links', function () {
    $result = process('<a href="https://internal.example.com/file.zip"></a>')->processLinks();
    expect($result->apply())->toBe('<a href="https://internal.example.com/file.zip" class="link--internal link--file"></a>');

    $result = process('<a href="file.zip"></a>')->processLinks();
    expect($result->apply())->toBe('<a href="file.zip" class="link--internal link--file"></a>');

    $result = process('<a href="/path/to/file.zip"></a>')->processLinks();
    expect($result->apply())->toBe('<a href="/path/to/file.zip" class="link--internal link--file"></a>');
});

test("Doesn't wrongly detect file links", function () {
    $result = process('<a href="https://internal.example.com/page.html"></a>')->processLinks();
    expect($result->apply())->toBe('<a href="https://internal.example.com/page.html" class="link--internal"></a>');
});

test('Processes external links', function () {
    $result = process('<a href="https://external.com"></a>')->processLinks();
    expect($result->apply())->toBe('<a href="https://external.com" class="link--external"></a>');
});

test('Treats subdomains as external links', function () {
    $result = process('<a href="https://external.internal.example.com"></a>')->processLinks();
    expect($result->apply())->toBe('<a href="https://external.internal.example.com" class="link--external"></a>');
});

test('Adds a class for invalid links', function () {
    $result = process('<a href="http://user@:80"></a>')->processLinks();
    expect($result->apply())->toBe('<a href="http://user@:80" class="link--invalid"></a>');
});

test('Provides a custom callback', function () {
    $result = process('<a href="https://internal.example.com">internal.example.com</a>')->processLinks(
        fn ($link) => $link->el->setAttribute('my:custom.attribute', '')
    );
    expect($result->apply())->toBe('<a href="https://internal.example.com" my:custom.attribute="">internal.example.com</a>');
});

test('Provides the default handler in the custom callback', function () {
    $result = process('<a href="https://internal.example.com">internal.example.com</a>')->processLinks(
        function ($link) {
            $link->el->setAttribute('my:custom.attribute', '');
            $link->addClasses();
        }
    );
    expect($result->apply())->toBe('<a href="https://internal.example.com" my:custom.attribute="" class="link--internal">internal.example.com</a>');
});

test('Allows to customize the prefix when adding classes', function () {
    $result = process('<a href="https://internal.example.com">internal.example.com</a>')->processLinks(
        fn ($link) => $link->addClasses('foo')
    );
    expect($result->apply())->toBe('<a href="https://internal.example.com" class="foo--internal">internal.example.com</a>');
});

test('Reliably detects URL types with port', function () {
    $_SERVER['HTTP_HOST'] = 'internal.example.com.ddev.site:33003';

    $input = '<a href="https://internal.example.com.ddev.site:33003">internal.example.com.ddev.site</a>';
    $expected = '<a href="https://internal.example.com.ddev.site:33003" class="link--internal">internal.example.com.ddev.site</a>';

    expect(process($input)->processLinks()->apply())->toBe($expected);
});

test('Gracefully handles an empty $_SERVER host', function () {
    $_SERVER['HTTP_HOST'] = null;

    $input = '<a href="https://internal.example.com.ddev.site:33003">internal.example.com.ddev.site</a>';
    $expected = '<a href="https://internal.example.com.ddev.site:33003" class="link--internal">internal.example.com.ddev.site</a>';

    expect(process($input)->processLinks()->apply())->toBe($expected);
});


test('Adds `rel="noopener noreferrer" to external links that open in a new tab', function () {
    $_SERVER['HTTP_HOST'] = "https://example.local";

    $input = '<a href="https://internal.example.com">internal.example.com</a>';
    $expected = '<a href="https://internal.example.com" class="link--external" target="_blank" rel="noopener noreferrer">internal.example.com</a>';

    expect(process($input)->processLinks(fn ($link) => $link
        ->addClasses()
        ->openExternalInNewTab())
        ->apply())
    ->toBe($expected);
});

test('Allows to disable `rel="noopener noreferrer"', function () {
    $_SERVER['HTTP_HOST'] = "https://example.local";

    $input = '<a href="https://internal.example.com">internal.example.com</a>';
    $expected = '<a href="https://internal.example.com" class="link--external" target="_blank">internal.example.com</a>';

    expect(process($input)->processLinks(fn ($link) => $link
        ->addClasses()
        ->openExternalInNewTab(false))
        ->apply())
    ->toBe($expected);
});
