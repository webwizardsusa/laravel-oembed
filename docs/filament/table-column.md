---
title: Table column
weight: 30
description: OEmbedColumn, a table column that shows each row's embed thumbnail and title, looked up for the whole page at once.
---

## Usage

```php
use Webwizardsusa\OEmbed\Filament\Tables\Columns\OEmbedColumn;

OEmbedColumn::make('video_url')->label('Video');
```

For each row, `OEmbedColumn` shows the embed's thumbnail and title, with the URL below the title. When there is
nothing to embed, it shows the URL on its own. An empty state, or one that isn't an `http(s)` URL, shows nothing. It
never loads a player: tables only show the thumbnail image.

It is a regular Filament `Column`, so `label()`, `toggleable()`, `searchable()`, `sortable()` and the like work on the
underlying URL column as usual.

## One lookup for the page

The first row that renders looks up the URLs of **every record on the table's current page** at once, with
[`resolveMany()`](../usage/embedder.md#many-urls-at-once). Stored answers come from the store, and the rest are fetched
in one pooled request, so a page of 25 new URLs costs one round of parallel requests rather than 25 in a row. Later
rows read the result.

The URL is read from each record with `data_get($record, $columnName)`, so a dotted name reaches a relationship:

```php
OEmbedColumn::make('video.url');
```

Eager load the relationship (`modifyQueryUsing(fn ($query) => $query->with('video'))`) to avoid a query per row.

## Embed settings

The column takes the same settings as the [form field](form-field.md#embed-settings):

```php
use Webwizardsusa\OEmbed\Embedder;
use Webwizardsusa\OEmbed\Filament\Tables\Columns\OEmbedColumn;

OEmbedColumn::make('video_url')
    ->withoutProviders(['tiktok'])
    ->discovery()
    ->embedder(fn (Embedder $embedder) => $embedder->store('database'));
```

`providers()`, `withProviders()`, `withoutProviders()`, `discovery()` and `privacyEnhancedYouTube()` have methods of
their own; anything else, such as `maxWidth()` and `store()`, goes through `embedder()`.

With discovery on, at most five uncached pages are discovered per table page; the other rows show their URL until a
later request has discovered them (see [Discovery](../security/discovery.md)).

## Content Security Policy

Thumbnails are loaded from the hosts providers answer with, such as `i.ytimg.com` for YouTube or `i.vimeocdn.com` for
Vimeo. If your panel sends a Content Security Policy, allow those hosts in `img-src`, or allow `https:` images.
Thumbnails are requested with `referrerpolicy="no-referrer"`.
