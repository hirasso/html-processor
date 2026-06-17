<?php

use function Hirasso\HTMLProcessor\process;

function obfuscateContacts(string $string, bool $email = true, bool $phone = true): string
{
    return process($string)->obfuscateContacts($email, $phone)->apply();
}

function expectEncoded(string $string): void
{
    expect(str_starts_with($string, '<a href="'))->toBe(true);
    expect(str_ends_with($string, '</a>'))->toBe(true);
    expect(substr_count($string, '#') > 10)->toBe(true);
}

test('Encodes Email addresses', function () {
    expectEncoded(obfuscateContacts('<a href="mailto:mail@example.com">mail@example.com</a>'));
});

test('encodes tel: href attribute', function () {
    $result = obfuscateContacts('<a href="tel:+491234567890">+49 123 456 7890</a>');

    expect($result)->toContain('</a>');
    // href is encoded (contains entities)
    expect($result)->toContain('&#');
    // visible text is untouched
    expect($result)->toContain('+49 123 456 7890');
    // original plain href is gone
    expect($result)->not->toContain('href="tel:+491234567890"');
});

test('leaves non-tel links untouched', function () {
    $input = '<a href="https://example.com">example</a>';
    expect(obfuscateContacts($input))->toContain('href="https://example.com"');
});

test('leaves plain text phone numbers untouched', function () {
    $input = '<p>Call us at +49 123 456 7890</p>';
    expect(obfuscateContacts($input))->toContain('+49 123 456 7890');
    expect(obfuscateContacts($input))->not->toContain('&#');
});
