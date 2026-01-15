---
"html-processor": minor
---

Change the API for `->typography()`:

```php
use Hirasso\HTMLProcessor\HTMLProcessor;

echo HTMLProcessor::fromString($html)
    ->typography('de_DE', fn ($typo) => $typo
        ->localizeQuotes() // format quotes based on locale
        ->preventWidows() // prevent widows
    );
```