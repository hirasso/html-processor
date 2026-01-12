<?php

use Hirasso\HTMLProcessor\HTMLProcessor;

test('Removes empty elements', function () {
    $result = HTMLProcessor::fromString("<p></p>")->removeEmptyElements()->process();
    expect($result)->toBe('');

    $result = HTMLProcessor::fromString("<p>\xc2\xa0</p>")->removeEmptyElements()->process();
    expect($result)->toBe('');

    $result = HTMLProcessor::fromString("<div>\xc2\xa0</div>")->removeEmptyElements('p,div')->process();
    expect($result)->toBe('');

    $result = HTMLProcessor::fromString("<p> &nbsp; </p>")->removeEmptyElements()->process();
    expect($result)->toBe('');
});


test('Preserves elements containing comments', function () {
    $result = HTMLProcessor::fromString("<p><!-- preserve me --></p>")->removeEmptyElements()->process();
    expect($result)->toBe('<p><!-- preserve me --></p>');
});

test('Preserves elements containing other elements', function () {
    $result = HTMLProcessor::fromString("<p><div></div></p>")->removeEmptyElements()->process();
    expect($result)->toBe('<p><div></div></p>');
});
