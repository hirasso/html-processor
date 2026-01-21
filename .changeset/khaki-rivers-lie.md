---
"html-processor": minor
---

Simplify the API for `processLinks`

```php
use function Hirasso\HTMLProcessor\process;

echo process($html)->processLinks(fn ($link) => $link->addClasses()->openExternalInNewTab());
```