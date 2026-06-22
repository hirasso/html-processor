<?php

use Hirasso\HTMLProcessor\Exceptions\DumpAndDieException;
use Hirasso\HTMLProcessor\Support\Support;
use Symfony\Component\VarDumper\VarDumper;

use function Hirasso\HTMLProcessor\process;

test('->dump() and continue', function () {
    $html = Support::trimLines(<<<HTML
    <p>Follow @acme on SocialWeb.</p>
    <p>See the tag #test on SocialWeb.</p>
    HTML);

    $dumps = [];
    $prevHandler = VarDumper::setHandler(
        function (mixed $var) use (&$dumps) {
            $dumps[] = Support::trimWhitespace($var);
        }
    );

    process($html)
        ->autolinkPrefix('@', 'https://your-instance.social/@') // link @profileName to Mastodon
        ->dump()
        ->autolinkPrefix('#', 'https://your-instance.social/tags') // link #hashTag to Mastodon
        ->dump()
        ->apply();

    VarDumper::setHandler($prevHandler);


    expect($dumps)->toBe([
        // first pass
        '<p>Follow <a href="https://your-instance.social/@acme">@acme</a> on SocialWeb.</p><p>See the tag #test on SocialWeb.</p>',
        // second pass
        '<p>Follow <a href="https://your-instance.social/@acme">@acme</a> on SocialWeb.</p><p>See the tag <a href="https://your-instance.social/tags/test">#test</a> on SocialWeb.</p>'
    ]);
});

test('->dd() and die', function () {
    $html = Support::trimLines(<<<HTML
    <p>Follow @acme on SocialWeb.</p>
    HTML);

    $output = '';
    $died = false;
    $prevHandler = VarDumper::setHandler(
        function (mixed $var) use (&$output) {
            $output .= $var;
        }
    );

    try {
        process($html)
            ->autolinkPrefix('@', 'https://your-instance.social/@') // link @profileName to Mastodon
            ->dd();
    } catch (DumpAndDieException) {
        $died = true;
    }

    VarDumper::setHandler($prevHandler);

    expect($died)->toBe(true);
    expect($output)->toContain('href="https://your-instance.social/@acme">@acme</a>');
});
