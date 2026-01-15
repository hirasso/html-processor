<?php

use Hirasso\HTMLProcessor\HTMLProcessor;

test('provides a helper function html()', function () {
    expect(html('foo')::class)->toBe(HTMLProcessor::class);
});
