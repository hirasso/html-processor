---
"html-processor": minor
---

Rework the API:

```php
echo HTMLProcessor::fromString($html)
    ->autolinkUrls() // wrap raw url strings in `<a>` tags
    ->autolinkPrefix('@', 'https://your-instance.social/@') // link @profileName to Mastodon
    ->autolinkPrefix('#', 'https://your-instance.social/tags') // link #hashTag to Mastodon
    ->removeEmptyElements('p') // remove empty paragraphs
    ->encodeEmails() // encode emails to confuse spam bots
    ->typography( // optimize typography
        'de_DE', // currently supported: 'en', 'de', 'fr'
        localizeQuotes: true, // format quotes based on locale
        preventWidows: true // prevent widows
    )
    ->processLinks(function ($el, $type) { // process links by callback
        if ($type === UrlType::External) {
            $el->setAttribute('target', '_blank');
        },
        addClasses: true // automatically add classes by type (mailto:, tel, internal, external, ...)
    });
```

