<?php

use Hirasso\HTMLProcessor\HTMLProcessor;

test('Removes empty paragraphs', function () {
    $result = HTMLProcessor::fromString("<p></p>")->beautify()->process();
    expect($result)->toBe('');

    $result = HTMLProcessor::fromString("<p>\xc2\xa0</p>")->beautify()->process();
    expect($result)->toBe('');

    $result = HTMLProcessor::fromString("<p> &nbsp; </p>")->beautify()->process();
    expect($result)->toBe('');
});

test('Prevents widows', function () {
    $result = HTMLProcessor::fromString("<p>I don't want to be a widow</p>")->beautify()->process();
    expect($result)->toBe('<p>I don\'t want to be a&nbsp;widow</p>');
});

test('Doesn\'t prevent widows in short strings', function () {
    $result = HTMLProcessor::fromString("<p>one two three</p>")->beautify()->process();
    expect($result)->toBe('<p>one two three</p>'); // no "&nbsp;"
});
