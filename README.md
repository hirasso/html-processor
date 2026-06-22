# hirasso/html-processor

[![Latest Version on Packagist](https://img.shields.io/packagist/v/hirasso/html-processor.svg?color=3ef09d)](https://packagist.org/packages/hirasso/html-processor)
[![Test Status](https://img.shields.io/github/actions/workflow/status/hirasso/html-processor/ci.yml?label=tests&color=3ef09d)](https://github.com/hirasso/html-processor/actions/workflows/ci.yml)
[![Code Coverage](https://img.shields.io/codecov/c/github/hirasso/html-processor?color=3ef09d)](https://app.codecov.io/gh/hirasso/html-processor)

**A tiny HTML processor written in PHP 🐘**

## Features (all optional)

- Automatically convert raw URLs to links
- Remove empty elements
- Process anchor `a` elements:
  - Add classes reflecting the link type (e.g. `link--external link--file`)
  - Open external links in a new tab
- Obfuscate email addresses and phone numbers to confuse spam bots (see [this article](https://spencermortensen.com/articles/email-obfuscation/))
- Automatically link prefixed words (e.g. `@mention` or `#hashtag`) to a URL of your choice
- Strip tags
- Conditionally apply any operation

## Promises

- Fluent API
- Fully compatible with HTML5
- All mutations are lazily queued and processed in one go
- Extensively tested

## Installation

```shell
composer require hirasso/html-processor
```

## Minimal Example

```php
use function Hirasso\HTMLProcessor\process;

echo process($html)->obfuscateEmails();
```

## Maximal Example

```php
use function Hirasso\HTMLProcessor\process;

echo process($html)
    ->autolinkUrls()
    ->removeEmptyElements('p')
    ->obfuscateEmails()
    ->processLinks(fn ($link) => $link->addClasses()->openExternalInNewTab())
    ->autolinkPrefix('@', 'https://your-instance.social/@')
    ->autolinkPrefix('#', 'https://your-instance.social/tags')
    // ->when() accepts a bool or a closure as the condition:
    ->when($isRichText, fn ($p) => $p->stripTags(allowedTags: ['p', 'a', 'strong', 'em']));

```

&rarr; Browse the <a href="./tests/Feature">tests folder</a> for more usage examples.
