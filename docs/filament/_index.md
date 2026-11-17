---
title: Filament
weight: 50
---

With Filament installed, the package adds three components that share the same embed settings:

- [`OEmbedInput`](form-field.md), a form field for a URL to embed, with a live preview.
- [`OEmbedEntry`](infolist-entry.md), an infolist entry that shows the embed.
- [`OEmbedColumn`](table-column.md), a table column that shows the thumbnail and title.

Filament is optional: the package works without it, and the components are only usable when `filament/filament` is
installed. Publish the package's panel stylesheets with `php artisan filament:assets` (see
[Installation](../installation.md#filament)).
