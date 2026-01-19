<?php

use function Hirasso\HTMLProcessor\process;

function runTest(string $str, string $locale, string $expected): void
{
    $result = process($str)
        ->typography(
            $locale,
            fn ($typo) => $typo
            ->localizeQuotes()
            ->preventWidows()
        )->apply();


    expect($result)->toBe($expected);
}

test('Ignores empty strings', function () {
    runTest("", 'de_DE', '');
});

test('Localizes german quotes', function () {
    $locale = 'de_DE';

    runTest("<p>\"Hallo\"</p>", $locale, '<p>„Hallo“</p>');
    runTest("<p>'Hallo'</p>", $locale, '<p>‚Hallo‘</p>');
    runTest("<p>\"'Hallo', sagte sie\"</p>", $locale, '<p>„‚Hallo‘, sagte sie“</p>');
});

test('Works with simple language codes', function () {
    runTest("<p>\"Hallo\"</p>", 'de', '<p>„Hallo“</p>');
});

test('Localizes english quotes', function () {
    $locale = 'en_US';

    runTest("<p>\"Hello\"</p>", $locale, '<p>“Hello”</p>');
    runTest("<p>'Hello'</p>", $locale, '<p>‘Hello’</p>');
    runTest("<p>\"'Hello', she said\"</p>", $locale, '<p>“‘Hello’, she said”</p>');
});

test('Localizes french quotes', function () {
    $locale = 'fr_FR';

    runTest("<p>\"Bonjour\"</p>", $locale, '<p>« Bonjour »</p>');
    runTest("<p>'Bonjour'</p>", $locale, '<p>‹ Bonjour ›</p>');
    runTest("<p>\"'Bonjour', dit-elle\"</p>", $locale, '<p>« ‹ Bonjour ›, dit-elle »</p>');

});

test('Parses locales robustly', function () {
    runTest("<p>\"Hallo\"</p>", 'de_DE', '<p>„Hallo“</p>');
    runTest("<p>\"Hallo\"</p>", 'de_DE_formal', '<p>„Hallo“</p>');
    runTest("<p>\"Hallo\"</p>", 'de-DE', '<p>„Hallo“</p>');
});

test('Ignores quotes in tags', function () {
    runTest('<p><a href="example.com">should be ignored</a></p>', 'de_DE', '<p><a href="example.com">should be ignored</a></p>');
});

test('Does nothing if the language is unknonwn', function () {
    runTest(
        '<p>"foo"</p>',
        'es_ES',
        '<p>"foo"</p>',
    );
});


test('Works when setting the locale late', function () {
    $str = "<p>\"Hallo\"</p>";
    $expected = '<p>„Hallo“</p>';

    $result = process($str)
        ->typography(
            'en_EN',
            fn ($typo) => $typo
            ->localizeQuotes()
            ->setLocale('de_DE')
        )->apply();
    expect($result)->toBe($expected);
});

test('Handles numeric entities for double quotes', function () {
    // &#8220; = " (left double quote), &#8221; = " (right double quote)
    runTest('<p>&#8220;Hallo&#8221;</p>', 'de_DE', '<p>„Hallo“</p>');
});

test('Handles numeric entities for single quotes', function () {
    // &#8216; = ' (left single quote), &#8217; = ' (right single quote)
    runTest('<p>&#8216;Hallo&#8217;</p>', 'de_DE', '<p>‚Hallo‘</p>');
});
