<?php

use Hirasso\HTMLProcessor\HTMLProcessor;

use function Hirasso\HTMLProcessor\html;

test('provides a helper function html()', function () {
    expect(html('foo')::class)->toBe(HTMLProcessor::class);
});
