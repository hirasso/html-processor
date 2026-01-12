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

test('Works on multiple paragraphs', function () {
    $string = trimLines(<<<HTML
    <p>This is the first paragraph</p>
    <p>This is the second paragraph</p>
    HTML);

    $expected = trimLines(<<<HTML
    <p>This is the first&nbsp;paragraph</p>
    <p>This is the second&nbsp;paragraph</p>
    HTML);

    $result = HTMLProcessor::fromString($string)->typography(localizeQuotes: false)->apply();
    expect($result)->toBe($expected);
});

test('only prevents widows at the very end of block elements', function () {
    $string = trimLines(<<<HTML
    <p>Basel and tutor at the Darmstadt Summer Course, the<i>Darmstadt Sonicals</i> is first and foremost.</p>
    HTML);

    $expected = trimLines(<<<HTML
    <p>Basel and tutor at the Darmstadt Summer Course, the<i>Darmstadt Sonicals</i> is first and&nbsp;foremost.</p>
    HTML);

    $result = HTMLProcessor::fromString($string)->typography(localizeQuotes: false)->apply();
    expect($result)->toBe($expected);
});

test('works without a parent element', function () {
    $string = trimLines(<<<HTML
    this text should have no widow
    HTML);

    $expected = trimLines(<<<HTML
    this text should have no&nbsp;widow
    HTML);

    $result = HTMLProcessor::fromString($string)->typography(localizeQuotes: false)->apply();
    expect($result)->toBe($expected);
});

test('works with nested elements', function () {
    $string = trimLines(<<<HTML
    <div><p>this text should have no widow</p> <p>And this shouold also be fixed</p></div>
    HTML);

    $expected = trimLines(<<<HTML
    <div><p>this text should have no&nbsp;widow</p> <p>And this shouold also be&nbsp;fixed</p></div>
    HTML);

    $result = HTMLProcessor::fromString($string)->typography(localizeQuotes: false)->apply();
    expect($result)->toBe($expected);
});
