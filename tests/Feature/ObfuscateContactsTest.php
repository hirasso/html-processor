<?php

use function Hirasso\HTMLProcessor\process;

function obfuscateEmail(string $string): string
{
    return process($string)->obfuscateContacts(email: true, phone: false)->apply();
}

function obfuscateTel(string $string): string
{
    return process($string)->obfuscateContacts(email: false, phone: true)->apply();
}

function obfuscateContacts(string $string, bool $email = true, bool $phone = true): string
{
    return process($string)->obfuscateContacts($email, $phone)->apply();
}

test('Encodes Email addresses', function () {
    $result = obfuscateContacts('<a href="mailto:mail@example.com">mail@example.com</a>');
    expect($result)->toContain('<a href="');
    expect($result)->toContain('</a>');
    expect($result)->toContain('&#');
});

test('encodes tel: href attribute and text content', function () {
    $result = obfuscateTel('<a href="tel:+491234567890">+49 123 456 7890</a>');

    // the quote is perserved
    expect($result)->toContain('<a href="');
    expect($result)->toContain('</a>');
    // href and text are encoded (contains entities)
    expect($result)->toContain('&#');
    // original plain href is gone
    expect($result)->not->toContain('href="tel:+491234567890"');
    // visible digits and + are encoded
    expect($result)->not->toContain('+49 123 456 7890');
});

test('encodes tel: with single quotes around href attribute', function () {
    $result = obfuscateTel("<a href='tel:+491234567890'>+49 123 456 7890</a>");

    // the quote is perserved
    expect($result)->toContain('<a href=\'');
    expect($result)->toContain('</a>');
    // href and text are encoded (contains entities)
    expect($result)->toContain('&#');
    // original plain href is gone
    expect($result)->not->toContain('href=\'tel:+491234567890\'');
    // visible digits and + are encoded
    expect($result)->not->toContain('+49 123 456 7890');
});

test('leaves non-tel links untouched', function () {
    $input = '<a href="https://example.com">example</a>';
    expect(obfuscateTel($input))->toContain('href="https://example.com"');
});

test('leaves plain text phone numbers untouched', function () {
    $input = '<p>Call us at +49 123 456 7890</p>';
    expect(obfuscateTel($input))->toContain('+49 123 456 7890');
    expect(obfuscateTel($input))->not->toContain('&#');
});

test('skips email encoding when email=false', function () {
    $result = obfuscateContacts('<a href="mailto:mail@example.com">mail@example.com</a>', email: false);
    expect($result)->toContain('mail@example.com');
    expect($result)->not->toContain('&#');
});

test('skips phone encoding when phone=false', function () {
    $result = obfuscateContacts('<a href="tel:+491234567890">+49 123 456 7890</a>', phone: false);
    expect($result)->toContain('href="tel:+491234567890"');
});

test('leaves non-tel anchor unchanged when tel: string is present elsewhere', function () {
    $input = '<a href="tel:+1234">call</a><a href="https://example.com">example</a>';
    $result = obfuscateTel($input);
    expect($result)->toContain('href="https://example.com"');
});
