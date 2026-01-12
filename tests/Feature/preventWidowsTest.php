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

// test('should work with this', function() {
//     $foo = '<p>is first and foremost a curatorial project in which the archive of the IMD is both a resource and a protagonist. Featuring recordings, letters, photos and documents from its 1946 foundation up to the present day, Deutsch&#8217;s deep dive research is striving to offer a coherent anthology of sonic transformations in contemporary music. Due to run over six years, the first edition – <i>the electric guitar diaries</i> – offers a compilation of 27 compositions available both on digital platforms and in physical format coming in the shape of a box set (3 vinyls and a booklet) filled with essays, photos and other deep cut rarities.</p>';
//     $bar = '<p>is first and foremost a curatorial project in which the archive of the IMD is both a resource and a protagonist. Featuring recordings, letters, photos and documents from its 1946 foundation up to the present day, Deutsch’s deep dive research is striving to offer a coherent anthology of sonic transformations in contemporary music. Due to run over six years, the first edition – <i>the electric guitar diaries</i> – offers a compilation of 27 compositions available both on digital platforms and in physical format coming in the shape of a box set (3 vinyls and a booklet) filled with essays, photos and other deep cut&nbsp;rarities.</p>';
//     $result = HTMLProcessor::fromString($foo)
//         ->typography(localizeQuotes: false)
//         ->apply();
//     expect($result)->toBe($bar);
// });
