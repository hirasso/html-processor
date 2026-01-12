<?php

use Hirasso\HTMLProcessor\HTMLProcessor;

test('Prevents widows', function () {
    $result = HTMLProcessor::fromString("<p>I don't want to be a widow</p>")->typography()->apply();
    expect($result)->toBe('<p>I don\'t want to be a&nbsp;widow</p>');
});

test('Doesn\'t prevent widows in short strings', function () {
    $result = HTMLProcessor::fromString("<p>one two three</p>")->typography()->apply();
    expect($result)->toBe('<p>one two three</p>'); // no "&nbsp;"
});
