---
"html-processor": patch
---

Fix widow prevention for text containing HTML entities (umlauts). The length check now correctly counts decoded characters instead of entity byte length, preventing false positives that skipped widow prevention on German text.
