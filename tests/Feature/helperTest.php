<?php

use function Hirasso\HTMLProcessor\html;

uses(PHPUnit\Framework\TestCase::class);

test('Returns a helper function html()', function () {
    expect(html('foo')::class)->toBe(Hirasso\HTMLProcessor\HTMLProcessor::class);
});
