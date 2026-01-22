<?php

use function Hirasso\HTMLProcessor\process;

beforeEach(function () {
    $_SERVER['HTTP_HOST'] = 'example.com';
});

afterEach(function () {
    $_SERVER['HTTP_HOST'] = null;
});

test('Processes mailto: links', function () {
    $result = process('<a href="mailto:mail@example.com"></a>')->processLinks();
    expect($result->apply())->toBe('<a href="mailto:mail@example.com" class="link--mailto"></a>');
});

test('Treats custom schemes as external', function () {
    $result = process('<a href="skype://example.com"></a>')->processLinks();
    expect($result->apply())->toBe('<a href="skype://example.com" class="link--external"></a>');
});

test('Processes tel: links', function () {
    $result = process('<a href="tel:123456"></a>')->processLinks();
    expect($result->apply())->toBe('<a href="tel:123456" class="link--tel"></a>');
});

test('Processes #anchor links', function () {
    $result = process('<a href="#some-anchor"></a>')->processLinks();
    expect($result->apply())->toBe('<a href="#some-anchor" class="link--anchor"></a>');
});

test('Processes internal links', function () {
    $result = process('<a href="https://example.com">example.com</a>')->processLinks();
    expect($result->apply())->toBe('<a href="https://example.com" class="link--internal">example.com</a>');
});

test('Processes local file links', function () {
    $result = process('<a href="https://example.com/file.zip"></a>')->processLinks();
    expect($result->apply())->toBe('<a href="https://example.com/file.zip" class="link--internal link--file link--ext--zip"></a>');

    $result = process('<a href="file.zip"></a>')->processLinks();
    expect($result->apply())->toBe('<a href="file.zip" class="link--internal link--file link--ext--zip"></a>');

    $result = process('<a href="/path/to/file.zip"></a>')->processLinks();
    expect($result->apply())->toBe('<a href="/path/to/file.zip" class="link--internal link--file link--ext--zip"></a>');
});

test("Doesn't wrongly detect file links", function () {
    $result = process('<a href="https://example.com/page.html"></a>')->processLinks();
    expect($result->apply())->toBe('<a href="https://example.com/page.html" class="link--internal link--ext--html"></a>');
});

test('Processes external links', function () {
    $result = process('<a href="https://external.com"></a>')->processLinks();
    expect($result->apply())->toBe('<a href="https://external.com" class="link--external"></a>');
});

test('Treats subdomains as external links', function () {
    $result = process('<a href="https://external.example.com"></a>')->processLinks();
    expect($result->apply())->toBe('<a href="https://external.example.com" class="link--external"></a>');
});

test('Adds a class for invalid links', function () {
    $result = process('<a href="http://user@:80"></a>')->processLinks();
    expect($result->apply())->toBe('<a href="http://user@:80" class="link--invalid"></a>');
});

test('Provides a custom callback', function () {
    $result = process('<a href="https://example.com">example.com</a>')->processLinks(
        fn ($link) => $link->el->setAttribute('my:custom.attribute', '')
    );
    expect($result->apply())->toBe('<a href="https://example.com" my:custom.attribute="">example.com</a>');
});

test('Provides the default handler in the custom callback', function () {
    $result = process('<a href="https://example.com">example.com</a>')->processLinks(
        function ($link) {
            $link->el->setAttribute('my:custom.attribute', '');
            $link->addClasses();
        }
    );
    expect($result->apply())->toBe('<a href="https://example.com" my:custom.attribute="" class="link--internal">example.com</a>');
});

test('Allows to customize the prefix when adding classes', function () {
    $result = process('<a href="https://example.com">example.com</a>')->processLinks(
        fn ($link) => $link->addClasses('foo')
    );
    expect($result->apply())->toBe('<a href="https://example.com" class="foo--internal">example.com</a>');
});

test('Reliably detects URL types with port', function () {
    $_SERVER['HTTP_HOST'] = 'example.com.ddev.site:33003';

    $input = '<a href="https://example.com.ddev.site:33003">example.com.ddev.site</a>';
    $expected = '<a href="https://example.com.ddev.site:33003" class="link--internal">example.com.ddev.site</a>';

    expect(process($input)->processLinks()->apply())->toBe($expected);
});

test('Gracefully handles an empty $_SERVER host', function () {
    $_SERVER['HTTP_HOST'] = null;

    $input = '<a href="https://example.com.ddev.site:33003">example.com.ddev.site</a>';
    $expected = '<a href="https://example.com.ddev.site:33003" class="link--internal">example.com.ddev.site</a>';

    expect(process($input)->processLinks()->apply())->toBe($expected);
});


test('Adds `rel="noopener noreferrer" to external links that open in a new tab', function () {
    $_SERVER['HTTP_HOST'] = "https://example.local";

    $input = '<a href="https://example.com">example.com</a>';
    $expected = '<a href="https://example.com" class="link--external" target="_blank" rel="noopener noreferrer">example.com</a>';

    expect(process($input)->processLinks(fn ($link) => $link
        ->addClasses()
        ->openExternalInNewTab())
        ->apply())
    ->toBe($expected);
});

test('Allows to disable `rel="noopener noreferrer"', function () {
    $_SERVER['HTTP_HOST'] = "https://example.local";

    $input = '<a href="https://example.com">example.com</a>';
    $expected = '<a href="https://example.com" class="link--external" target="_blank">example.com</a>';

    expect(process($input)->processLinks(fn ($link) => $link
        ->addClasses()
        ->openExternalInNewTab(false))
        ->apply())
    ->toBe($expected);
});
