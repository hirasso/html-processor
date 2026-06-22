<?php

use function Hirasso\HTMLProcessor\process;

test('Autolinks URLs', function () {
    $result = process("http://example.com")->autolinkUrls()->apply();
    expect($result)->toContain('<a href="http://example.com">example.com</a>');
});

test('Requires a scheme before autolinking', function () {
    $result = process("example.com")->autolinkUrls()->apply();
    expect($result)->toBe('example.com');
});

test('Autolinks Emails', function () {
    $result = process("mail@example.com")->autolinkUrls()->apply();
    expect($result)->toBe('<a href="mailto:mail@example.com">mail@example.com</a>');
});

test('Truncates autolinked URLs', function () {
    $result = process("https://example.com/very-long-url-that-should-absolutely-be-truncated")->autolinkUrls()->apply();
    expect($result)->toBe('<a href="https://example.com/very-long-url-that-should-absolutely-be-truncated">example.com/very-long-url-that-s...</a>');
});

test('Ignores already-linked mailto: links', function () {
    $result = process('<a href="mailto:mail@example.com">mail@example.com</a>')->autolinkUrls()->apply();
    expect($result)->toBe('<a href="mailto:mail@example.com">mail@example.com</a>');
});

test('Works together with processLinks() in one Dom\HTMLDocument pass', function () {
    $_SERVER['HTTP_HOST'] = 'https://example.local.com';

    $result = process("https://example.com")->autolinkUrls()->processLinks()->apply();
    expect($result)->toBe('<a href="https://example.com" class="link--external">example.com</a>');

    $result = process("https://example.com")->processLinks()->autolinkUrls()->apply();
    expect($result)->toBe('<a href="https://example.com" class="link--external">example.com</a>');

    $_SERVER['HTTP_HOST'] = '';
});
