<?php

use function Hirasso\HTMLProcessor\process;

test('Removes empty elements', function () {
    $result = process("<p></p>")->removeEmptyElements()->apply();
    expect($result)->toBe('');

    $result = process("<p>\xc2\xa0</p>")->removeEmptyElements()->apply();
    expect($result)->toBe('');

    $result = process("<div>\xc2\xa0</div>")->removeEmptyElements('p,div')->apply();
    expect($result)->toBe('');

    $result = process("<p> &nbsp; </p>")->removeEmptyElements()->apply();
    expect($result)->toBe('');
});


test('Preserves elements containing comments', function () {
    $result = process("<p><!-- preserve me --></p>")->removeEmptyElements()->apply();
    expect($result)->toBe('<p><!-- preserve me --></p>');
});

test('Preserves elements containing other elements', function () {
    $result = process("<p><span></span></p>")->removeEmptyElements()->apply();
    expect($result)->toBe('<p><span></span></p>');
});
