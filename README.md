# hirasso/html-processor

[![Latest Version on Packagist](https://img.shields.io/packagist/v/hirasso/html-processor.svg?color=3ef09d)](https://packagist.org/packages/hirasso/html-processor)
[![Test Status](https://img.shields.io/github/actions/workflow/status/hirasso/html-processor/ci.yml?label=tests&color=3ef09d)](https://github.com/hirasso/html-processor/actions/workflows/ci.yml)
[![Code Coverage](https://img.shields.io/codecov/c/github/hirasso/html-processor?color=3ef09d)](https://app.codecov.io/gh/hirasso/html-processor)

**A tiny HTML processor written in PHP 🐘**

> [!WARNING]
> The API hasn't stabilized, yet. Use with caution, ideally in combination with a tool like
> [phpstan/phpstan](https://github.com/phpstan/phpstan)

## Features (all optional)

- Automatically convert raw URLs to links
- Remove empty elements
- Optimize typography:
  - Localize quotes (currently supported languages, `en`, `de`, `fr`)
  - Avoid short last lines (traditionally called "Widows")
- Process links:
  - Add link classes based on type (e.g. `link--external link--file link--ext--pdf`)
  - Open external links in new tab
- Encode email addresses to confuse spam bots
- Automatically link prefixed words (e.g. `@mention` or `#hashtag`) to a URL of your choice

## Promises

- Fluent API
- Understands HTML5
- Optimized for performance
- Extensively tested

## Installation

```shell
composer require hirasso/html-processor
```

## Minimal Example

```php
use function Hirasso\HTMLProcessor\process;

echo process($html)->typography('de');
```

## Maximal Example

```php
use function Hirasso\HTMLProcessor\process;

echo process($html)
    ->autolinkUrls()
    ->removeEmptyElements('p')
    ->encodeEmails()
    ->typography('de', fn ($typo) => $typo->localizeQuotes()->avoidShortLastLines())
    ->processLinks(fn ($link) => $link->addClasses()->openExternalInNewTab())
    ->autolinkPrefix('@', 'https://your-instance.social/@')
    ->autolinkPrefix('#', 'https://your-instance.social/tags');

```

- Browse the <a href="./tests/Feature">tests/Feature folder</a> for more usage examples.
- See the [list of currently supported quote styles](./src/Service/DOM/QuoteLocalizer/QuoteStyle.php) here.