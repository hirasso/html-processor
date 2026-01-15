---
"html-processor": minor
---

Change the API for `->typography()`:

```php
use Hirasso\HTMLProcessor\HTMLProcessor;

echo HTMLProcessor::fromString($html)
    ->typography(fn ($typo) => $typo
        ->setLocale('de_DE') // currently supported: 'en', 'de', 'fr'
        ->localizeQuotes() // format quotes based on locale
        ->preventWidows() // prevent widows
    )
```