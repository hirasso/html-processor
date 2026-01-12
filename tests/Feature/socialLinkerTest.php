<?php

use Hirasso\HTMLProcessor\HTMLProcessor;

test('Autolinks @mentions to Instagram', function () {
    $result = HTMLProcessor::fromString("@hirasso")->autolinkSocial('@', 'https://www.instagram.com')->process();
    expect($result)->toBe('<a href="https://www.instagram.com/hirasso">@hirasso</a>');
});

test('Autolinks #tags to Instagram', function () {
    $result = HTMLProcessor::fromString("#tag")->autolinkSocial('#', 'https://www.instagram.com/explore/tags')->process();
    expect($result)->toBe('<a href="https://www.instagram.com/explore/tags/tag">#tag</a>');
});
