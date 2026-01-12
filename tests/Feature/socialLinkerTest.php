<?php

use Hirasso\HTMLProcessor\HTMLProcessor;

test('Links @mentions to Instagram', function () {
    $result = HTMLProcessor::fromString("@hirasso")->autolinkSocial('@', 'https://www.instagram.com/')->process();
    expect($result)->toBe('<a href="https://www.instagram.com/hirasso">@hirasso</a>');
});

test('Links #tags to Instagram', function () {
    $result = HTMLProcessor::fromString("#tag")->autolinkSocial('#', 'https://www.instagram.com/explore/tags/')->process();
    expect($result)->toBe('<a href="https://www.instagram.com/explore/tags/tag">#tag</a>');
});

test('Links @mentions to custom url', function () {
    $result = HTMLProcessor::fromString("@hirasso")->autolinkSocial('@', 'https://your-instance.social/@')->process();
    expect($result)->toBe('<a href="https://your-instance.social/@hirasso">@hirasso</a>');
});

test('Links #tags to custom url', function () {
    $result = HTMLProcessor::fromString("#php")->autolinkSocial('#', 'https://your-instance.social/tags')->process();
    expect($result)->toBe('<a href="https://your-instance.social/tags/php">#php</a>');
});
