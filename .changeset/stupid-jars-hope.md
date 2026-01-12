---
"html-processor": patch
---

Only allow duplicate IDs with a LIBXML_VERSION lower then 21000.

Otherwise, HTML might not be parsed correctly (observed with
tags and multiple lines)