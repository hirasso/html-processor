---
"html-processor": patch
---

Rename the helper function `html()` to `process()`:

```php
use function Hirasso\HTMLProcessor\process;

echo process($html)
    ->autolinkUrls() // wrap raw url strings in `<a>` tags
    ->removeEmptyElements() // remove empty paragraphs
    ->encodeEmails() // encode emails to confuse spam bots
    ->typography('de_DE') // fix typography based on locale
    ->processLinks(); // add classes based on link type
```