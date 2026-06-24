<?php

use Hirasso\HTMLObfuscator\HTMLObfuscator;

use function Hirasso\HTMLProcessor\process;

function obfuscate(string $string, bool $injectJS = false): string
{
    HTMLObfuscator::$jsInjected = false;

    return process($string)->obfuscate(
        fn ($o) => $o->passphrase('testing')->randomizeKey(false)->injectDeobfuscationScript($injectJS)
    )->apply();
}

test('Obfuscates emails in links', function () {
    $result = obfuscate('<a href="mailto:mail@example.com">email</a>');

    expect($result)->toBe('<obfuscated-element value="XQQSCkMDBVwXXFRQWE0KDwlUXQoiV0oDVRUIXBtWWFhDW1cPUA8PXRpQCw==" key="ae2b1fca515949e5d54fb22b8ed95575" type="element"></obfuscated-element>');
});

test('Obfuscates emails in plaintext', function () {
    $result = obfuscate('mail@example.com');

    expect($result)->toBe('<obfuscated-element value="DARbDnEDGwBYQVlcGloKWA==" key="ae2b1fca515949e5d54fb22b8ed95575"></obfuscated-element>');
});

test('Obfuscates phone numbers in links', function () {
    $result = obfuscate('<a href="tel:+49 12 345 67">call us</a>');

    expect($result)->toBe('<obfuscated-element value="XQQSCkMDBVwXRVBVDhJRDEQEBkZRBgdCDlJGB1ZUW1lBEEFeHgdd" key="ae2b1fca515949e5d54fb22b8ed95575" type="element"></obfuscated-element>');
});

test('Obfuscates phone numbers in plaintext', function () {
    $result = obfuscate('+49 12 345 67');

    expect($result)->toBe('<obfuscated-element value="SlELQgBUQ1IBBBUPAw==" key="ae2b1fca515949e5d54fb22b8ed95575"></obfuscated-element>');
});

test('Injects the deobfuscation JavaScript by default', function () {
    $result = obfuscate('+49 12 345 67', injectJS: true);

    expect($result)->toContain('<script');
    expect($result)->toContain('@ts-check');
    expect($result)->toContain('class ObfuscatedElement extends HTMLElement');
});
