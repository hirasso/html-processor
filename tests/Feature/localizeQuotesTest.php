<?php

use Hirasso\HTMLProcessor\HTMLProcessor;

function runTest(string $str, string $locale, string $expected): void
{
    $result = HTMLProcessor::fromString($str)
        ->typography(
            fn ($typo) => $typo
            ->setLocale($locale)
            ->localizeQuotes()
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


test('Works when setting the locale late', function () {
    $str = "<p>\"Hallo\"</p>";
    $expected = '<p>„Hallo“</p>';

    $result = HTMLProcessor::fromString($str)
        ->typography(
            fn ($typo) => $typo
            ->localizeQuotes()
            ->setLocale('de_DE')
        )->apply();
    expect($result)->toBe($expected);
});
