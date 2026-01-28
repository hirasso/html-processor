<?php

use function Hirasso\HTMLProcessor\process;

function normalize(string $str): string
{
    return process($str)->typography('en', fn ($typo) => $typo->normalizeQuotes())->apply();
}

test('Basic quote wrapping', function () {
    expect(normalize('<p>"hello"</p>'))->toBe('<p><q>hello</q></p>');
    expect(normalize("<p>'hello'</p>"))->toBe('<p><q>hello</q></p>');
});

test('Nested quotes', function () {
    expect(normalize('<p>"outer \'inner\' outer"</p>'))->toBe(
        '<p><q>outer <q>inner</q> outer</q></p>'
    );
});

test('Apostrophes preserved', function () {
    expect(normalize("<p>don't</p>"))->toBe("<p>don't</p>");
    expect(normalize("<p>Edit's</p>"))->toBe("<p>Edit's</p>");
});

test('Unmatched quotes stay as-is', function () {
    expect(normalize('<p>„unclosed</p>'))->toBe('<p>„unclosed</p>');
    expect(normalize('<p>unopened"</p>'))->toBe('<p>unopened"</p>');
});

test('Normalizes curly quotes before wrapping', function () {
    // Curly quotes should be treated like regular quotes
    expect(normalize("<p>\u{201C}hello\u{201D}</p>"))->toBe('<p><q>hello</q></p>');
    expect(normalize("<p>\u{2018}hello\u{2019}</p>"))->toBe('<p><q>hello</q></p>');
});

test('Mixed quote types nested', function () {
    expect(normalize('<p>"she said \'hello\' to him"</p>'))->toBe(
        '<p><q>she said <q>hello</q> to him</q></p>'
    );
});

test('Multiple separate quoted sections', function () {
    expect(normalize('<p>"first" and "second"</p>'))->toBe(
        '<p><q>first</q> and <q>second</q></p>'
    );
});

test('Handles empty strings', function () {
    expect(normalize(''))->toBe('');
});

test('Ignores quotes in HTML attributes', function () {
    expect(normalize('<p><a href="example.com">"link"</a></p>'))->toBe(
        '<p><a href="example.com"><q>link</q></a></p>'
    );
});

test('Works with surrounding text', function () {
    expect(normalize('<p>He said "hello" to her</p>'))->toBe(
        '<p>He said <q>hello</q> to her</p>'
    );
});

test('Deeply nested quotes', function () {
    expect(normalize('<p>"a \'b "c" b\' a"</p>'))->toBe(
        '<p><q>a <q>b <q>c</q> b</q> a</q></p>'
    );
});

test('Inch notation', function () {
    expect(normalize('<p>"some 12" long"</p>'))->toBe(
        '<p><q>some 12" long</q></p>'
    );
});
