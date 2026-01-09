<?php

use Hirasso\HTMLProcessor\HTMLProcessor;
use IvoPetkov\HTML5DOMElement;

beforeAll(function () {
    $_SERVER['HTTP_HOST'] = 'example.com';
});

afterAll(function () {
    $_SERVER['HTTP_HOST'] = null;
});

test('Processes mailto: links', function () {
    $result = HTMLProcessor::fromString('<a href="mailto:mail@example.com"></a>')->processLinks();
    expect($result->toHTML())->toBe('<a href="mailto:mail@example.com" class="link--mailto"></a>');
});

test('Processes tel: links', function () {
    $result = HTMLProcessor::fromString('<a href="tel:123456"></a>')->processLinks();
    expect($result->toHTML())->toBe('<a href="tel:123456" class="link--tel"></a>');
});

test('Processes #anchor links', function () {
    $result = HTMLProcessor::fromString('<a href="#some-anchor"></a>')->processLinks();
    expect($result->toHTML())->toBe('<a href="#some-anchor" class="link--anchor"></a>');
});

test('Processes internal links', function () {
    $result = HTMLProcessor::fromString('<a href="https://example.com">example.com</a>')->processLinks();
    expect($result->toHTML())->toBe('<a href="https://example.com" class="link--internal">example.com</a>');
});

test('Processes local file links', function () {
    $result = HTMLProcessor::fromString('<a href="https://example.com/file.zip"></a>')->processLinks();
    expect($result->toHTML())->toBe('<a href="https://example.com/file.zip" class="link--internal link--file link--file-zip"></a>');

    $result = HTMLProcessor::fromString('<a href="file.zip"></a>')->processLinks();
    expect($result->toHTML())->toBe('<a href="file.zip" class="link--internal link--file link--file-zip"></a>');

    $result = HTMLProcessor::fromString('<a href="/path/to/file.zip"></a>')->processLinks();
    expect($result->toHTML())->toBe('<a href="/path/to/file.zip" class="link--internal link--file link--file-zip"></a>');
});

test("Doesn't wrongly detect file links", function () {
    $result = HTMLProcessor::fromString('<a href="https://example.com/page.html"></a>')->processLinks();
    expect($result->toHTML())->toBe('<a href="https://example.com/page.html" class="link--internal"></a>');
});

test('Processes external links', function () {
    $result = HTMLProcessor::fromString('<a href="https://external.com"></a>')->processLinks();
    expect($result->toHTML())->toBe('<a href="https://external.com" target="_blank" class="link--external"></a>');
});

test('Treats subdomains as external links', function () {
    $result = HTMLProcessor::fromString('<a href="https://external.example.com"></a>')->processLinks();
    expect($result->toHTML())->toBe('<a href="https://external.example.com" target="_blank" class="link--external"></a>');
});

test('Provides a callback for link processing', function () {
    $result = HTMLProcessor::fromString('<a href="https://example.com">example.com</a>')->processLinks(
        fn (HTML5DOMElement $el) => $el->setAttribute('x-typowave.notouch.minvw.768', '')
    );
    expect($result->toHTML())->toBe('<a href="https://example.com" class="link--internal" x-typowave.notouch.minvw.768="">example.com</a>');
});
