<?php

use Hirasso\HTMLProcessor\Uri\Uri;

test('Uri::fromString() normalizes scheme and host', function () {
    $uri = Uri::fromString('HTTPS://EXAMPLE.com');

    expect($uri->getScheme())->toBe('https');
    expect($uri->getHost())->toBe('example.com');
});
