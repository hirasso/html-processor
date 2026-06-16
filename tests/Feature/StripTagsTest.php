<?php

use function Hirasso\HTMLProcessor\process;

test('Strips tags', function () {
    $result = process("<p></p>")->stripTags()->apply();
    expect($result)->toBe('');
});

test('Strips tags, with allow list', function () {
    $result = process("<div><p></p></div>")->stripTags('<p>')->apply();
    expect($result)->toBe('<p></p>');

    $result = process("<div><p></p></div>")->stripTags(['p'])->apply();
    expect($result)->toBe('<p></p>');
});
