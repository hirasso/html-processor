<?php

use function Hirasso\HTMLProcessor\process;

function runNormalizeTest(string $str, string $expected): void
{
    $result = process($str)
        ->typography(
            'en',
            fn ($typo) => $typo->normalizeQuotes()
        )->apply();

    expect($result)->toBe($expected);
}

test('Basic quote wrapping', function () {
    runNormalizeTest('<p>"hello"</p>', '<p><q>hello</q></p>');
    runNormalizeTest("<p>'hello'</p>", '<p><q>hello</q></p>');
});

test('Nested quotes', function () {
    runNormalizeTest(
        '<p>"outer \'inner\' outer"</p>',
        '<p><q>outer <q>inner</q> outer</q></p>'
    );
});

test('Apostrophes preserved', function () {
    runNormalizeTest("<p>don't</p>", "<p>don't</p>");
    runNormalizeTest("<p>Edit's</p>", "<p>Edit's</p>");
});

test('Unmatched quotes stay as-is', function () {
    runNormalizeTest('<p>"unclosed</p>', '<p>"unclosed</p>');
    runNormalizeTest('<p>unopened"</p>', '<p>unopened"</p>');
});

test('Normalizes curly quotes before wrapping', function () {
    // Curly quotes should be treated like regular quotes
    runNormalizeTest("<p>\u{201C}hello\u{201D}</p>", '<p><q>hello</q></p>');
    runNormalizeTest("<p>\u{2018}hello\u{2019}</p>", '<p><q>hello</q></p>');
});

test('Mixed quote types nested', function () {
    runNormalizeTest(
        '<p>"she said \'hello\' to him"</p>',
        '<p><q>she said <q>hello</q> to him</q></p>'
    );
});

test('Multiple separate quoted sections', function () {
    runNormalizeTest(
        '<p>"first" and "second"</p>',
        '<p><q>first</q> and <q>second</q></p>'
    );
});

test('Handles empty strings', function () {
    runNormalizeTest('', '');
});

test('Ignores quotes in HTML attributes', function () {
    runNormalizeTest(
        '<p><a href="example.com">"link"</a></p>',
        '<p><a href="example.com"><q>link</q></a></p>'
    );
});

test('Works with surrounding text', function () {
    runNormalizeTest(
        '<p>He said "hello" to her</p>',
        '<p>He said <q>hello</q> to her</p>'
    );
});

test('Deeply nested quotes', function () {
    runNormalizeTest(
        '<p>"a \'b "c" b\' a"</p>',
        '<p><q>a <q>b <q>c</q> b</q> a</q></p>'
    );
});
