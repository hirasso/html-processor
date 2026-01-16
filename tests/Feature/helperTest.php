<?php

use function Hirasso\HTMLProcessor\process;

uses(PHPUnit\Framework\TestCase::class);

test('Returns a helper function process()', function () {
    expect(process('foo')::class)->toBe(Hirasso\HTMLProcessor\HTMLProcessor::class);
});
