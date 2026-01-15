<?php

use function Hirasso\HTMLProcessor\html;

test('Removes empty elements', function () {
    $result = html("<p></p>")->removeEmptyElements()->apply();
    expect($result)->toBe('');

    $result = html("<p>\xc2\xa0</p>")->removeEmptyElements()->apply();
    expect($result)->toBe('');

    $result = html("<div>\xc2\xa0</div>")->removeEmptyElements('p,div')->apply();
    expect($result)->toBe('');

    $result = html("<p> &nbsp; </p>")->removeEmptyElements()->apply();
    expect($result)->toBe('');
});


test('Preserves elements containing comments', function () {
    $result = html("<p><!-- preserve me --></p>")->removeEmptyElements()->apply();
    expect($result)->toBe('<p><!-- preserve me --></p>');
});

test('Preserves elements containing other elements', function () {
    $result = html("<p><span></span></p>")->removeEmptyElements()->apply();
    expect($result)->toBe('<p><span></span></p>');
});
