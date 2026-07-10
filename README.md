> [!NOTE]
> This package is abandoned in favor of two new packages: [hirasso/prose](https://github.com/hirasso/prose/) and [hirasso/html-obfuscator](https://github.com/hirasso/html-obfuscator).

# hirasso/html-processor

**A tiny HTML processor written in PHP 🐘**

## Features (all optional)

- Automatically convert raw URLs to links
- Remove empty elements
- Process anchor `a` elements:
  - Add classes reflecting the link type (e.g. `link--external link--file`)
  - Open external links in a new tab
- Obfuscate email addresses and phone numbers to confuse spam bots (see [this article](https://spencermortensen.com/articles/email-obfuscation/))
- Automatically link prefixed words (e.g. `@mention` or `#hashtag`) to a URL of your choice
- Apply arbitrary DOM mutations with full access to the `HTMLDocument`
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

/** remove empty parahraphs from HTML */
echo process($html)->removeEmptyElements('p');
```

## Maximal Example

```php
use function Hirasso\HTMLProcessor\process;

echo process($html)
    ->autolinkUrls()
    ->removeEmptyElements('p')
    ->processLinks(fn ($link) => $link->addClasses()->openExternalInNewTab())
    ->autolinkPrefix('@', 'https://your-instance.social/@')
    ->autolinkPrefix('#', 'https://your-instance.social/tags')
    /** ->mutate() gives you direct access to the HTMLDocument: */
    ->mutate(fn (\Dom\HTMLDocument $doc) => $doc->querySelector('h1')?->setAttribute('class', 'text-2xl'))
    /** ->when() accepts a bool or a closure as the condition: */
    ->when($isRichText, fn ($p) => $p->stripTags(allowedTags: ['p', 'a', 'strong', 'em']));

```

&rarr; Browse the <a href="./tests/">tests folder</a> for more usage examples.
