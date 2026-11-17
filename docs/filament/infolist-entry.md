---
title: Infolist entry
weight: 20
description: OEmbedEntry, an infolist entry that shows the embed for a URL.
---

## Usage

```php
use Webwizardsusa\OEmbed\Filament\Infolists\Components\OEmbedEntry;

OEmbedEntry::make('video_url')->label('Video');
```

`OEmbedEntry` shows the embed for the entry's state, exactly as [`render()`](../usage/embedder.md#rendering) writes
it: the provider's player, post or photo, or the URL as a link when there is nothing to embed. An empty state, or one
that isn't an `http(s)` URL, shows nothing.

It is a regular Filament `Entry`, so `label()`, `hiddenLabel()`, `columnSpanFull()`, `visible()` and the rest work as
usual. Players fill the entry's width and keep their aspect ratio, so wide layouts work best:

```php
OEmbedEntry::make('video_url')->hiddenLabel()->columnSpanFull();
```

The embed styles are registered in your panels by the package; run `php artisan filament:assets` after installing
(see [Installation](../installation.md#filament)).

## Embed settings

The entry takes the same settings as the [form field](form-field.md#embed-settings):

```php
use Webwizardsusa\OEmbed\Embedder;
use Webwizardsusa\OEmbed\Filament\Infolists\Components\OEmbedEntry;

OEmbedEntry::make('video_url')
    ->withoutProviders(['tiktok'])
    ->discovery()
    ->privacyEnhancedYouTube()
    ->embedder(fn (Embedder $embedder) => $embedder->maxWidth(640));
```

`providers()`, `withProviders()`, `withoutProviders()`, `discovery()` and `privacyEnhancedYouTube()` have methods of
their own. Everything else, such as `maxWidth()` and `store()`, goes through `embedder()`, which takes a closure that
changes the entry's embedder or an `Embedder` instance to use instead.

Use the same settings as the form field that saved the URL, so the answer stored during validation is read back
rather than looked up again.

## Content Security Policy

The entry loads players, posts' scripts and photos in the panel, like any page that shows embeds. If your panel sends
a Content Security Policy, allow the hosts in [Content Security Policy](../security/content-security-policy.md).
