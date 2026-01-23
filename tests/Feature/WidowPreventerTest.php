<?php

use function Hirasso\HTMLProcessor\process;

test('Prevents widows', function () {
    $result = process("<p>I don't want to be a widow</p>")
        ->typography('en_US')
        ->apply();
    expect($result)->toBe('<p>I don\'t want to be a&nbsp;widow</p>');
});

test('Doesn\'t prevent widows in short strings', function () {
    $result = process("<p>one two three</p>")->typography('en_US')->apply();
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

    $result = process($string)
        ->typography('de_DE', fn ($typo) => $typo
            ->avoidShortLastLines())
        ->apply();
    expect($result)->toBe($expected);
});

test('only prevents widows at the very end of block elements', function () {
    $string = trimLines(<<<HTML
    <p>Basel and tutor at the Darmstadt Summer Course, the<i>Darmstadt Sonicals</i> is first and foremost.</p>
    HTML);

    $expected = trimLines(<<<HTML
    <p>Basel and tutor at the Darmstadt Summer Course, the<i>Darmstadt Sonicals</i> is first and&nbsp;foremost.</p>
    HTML);

    $result = process($string)
        ->typography('en', fn ($typo) => $typo->avoidShortLastLines())
        ->apply();
    expect($result)->toBe($expected);
});

test('works without a parent element', function () {
    $string = trimLines(<<<HTML
    this text should have no widow
    HTML);

    $expected = trimLines(<<<HTML
    this text should have no&nbsp;widow
    HTML);

    $result = process($string)
        ->typography('en', fn ($typo) => $typo->avoidShortLastLines())
        ->apply();
    expect($result)->toBe($expected);
});

test('works with nested elements', function () {
    $string = trimLines(<<<HTML
    <div><p>this text should have a widow</p> <p>And this shouold also have one</p></div>
    HTML);

    $expected = trimLines(<<<HTML
    <div><p>this text should have a&nbsp;widow</p> <p>And this shouold also have&nbsp;one</p></div>
    HTML);

    $result = process($string)
        ->typography('en', fn ($typo) => $typo->avoidShortLastLines())
        ->apply();
    expect($result)->toBe($expected);
});

test('should work with this', function () {
    $foo = trimLines(<<<HTML
    <p>Die Zukunft der Stadt ist kleinräumig durchmischt, möglichst dicht bespielt und klimagerecht. Das Rohmaterial aus dem sie immer wieder neu entsteht, wird in den meisten Fällen mit den bestehenden Bauten bereits vorhanden sein.</p>
    <p>https://google.com</p>
    <p>Für die Umnutzung des bisherigen Bürogebäudes an der Schärenmoosstrasse in Zürich Leutschenbach für die Stiftung PWG schlagen wir daher eine Wohntypologie vor, die Wohnen und Arbeiten räumlich und zeitlich flexibel miteinander vereinbart: Kompakt geschnittene, private Wohneinheiten werden durch vorgelagerte «Shared Spaces» erweitert. Was hier stattfinden soll, bestimmen die sechs bis zwölf direkten Anrainer:innen selbst!</p>
    <p>Die zur Verfügung stehende Gesamtfläche wird also nur zum Teil von primärem Wohnraum belegt, die verbleibende Fläche bildet den Spielraum auf dem die Nutzungsdichte – auch um Arbeitsplätze – erhöht werden kann. Die Nutzung kann und soll immer wieder neu verhandelt werden und die räumlichen Anliegen der Bewohner:innen heute und in Zukunft erfüllen können.</p>
    HTML);

    $bar = trimLines(<<<HTML
    <p>Die Zukunft der Stadt ist kleinräumig durchmischt, möglichst dicht bespielt und klimagerecht. Das Rohmaterial aus dem sie immer wieder neu entsteht, wird in den meisten Fällen mit den bestehenden Bauten bereits vorhanden&nbsp;sein.</p>
    <p>https://google.com</p>
    <p>Für die Umnutzung des bisherigen Bürogebäudes an der Schärenmoosstrasse in Zürich Leutschenbach für die Stiftung PWG schlagen wir daher eine Wohntypologie vor, die Wohnen und Arbeiten räumlich und zeitlich flexibel miteinander vereinbart: Kompakt geschnittene, private Wohneinheiten werden durch vorgelagerte «Shared Spaces» erweitert. Was hier stattfinden soll, bestimmen die sechs bis zwölf direkten Anrainer:innen&nbsp;selbst!</p>
    <p>Die zur Verfügung stehende Gesamtfläche wird also nur zum Teil von primärem Wohnraum belegt, die verbleibende Fläche bildet den Spielraum auf dem die Nutzungsdichte – auch um Arbeitsplätze – erhöht werden kann. Die Nutzung kann und soll immer wieder neu verhandelt werden und die räumlichen Anliegen der Bewohner:innen heute und in Zukunft erfüllen&nbsp;können.</p>
    HTML);

    $result = process($foo)
        ->typography('en', fn ($typo) => $typo->avoidShortLastLines())
        ->apply();
    expect($result)->toBe($bar);
});
