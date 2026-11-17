---
title: Introduction
weight: 10
description: What Laravel oEmbed does, a short example, and what it needs.
---

Laravel oEmbed turns links to YouTube, Vimeo, X and other sites into embeds: a video player, a post, a photo, or a
plain link when there is nothing to embed. It asks the site's [oEmbed](https://oembed.com) endpoint about the URL,
keeps the answer, and builds the markup you print.

The provider's HTML is never printed as it came. Players are rebuilt from the provider's own hosts, posts from their
text and links, and anything else shows as a link. That makes it safe to embed URLs your users paste.

```blade
<style>{!! OEmbed::stylesheet() !!}</style>

<x-oembed :url="$post->video_url" caption="Watch this" />
```

```php
use Webwizardsusa\OEmbed\Facades\OEmbed;

$embed = OEmbed::resolve('https://www.youtube.com/watch?v=dQw4w9WgXcQ');

$embed?->title();          // "Rick Astley - Never Gonna Give You Up"
$embed?->thumbnail();      // "https://i.ytimg.com/vi/…"
$embed?->toHtml();         // <figure class="oembed" data-kind="video">…</figure>
```

## What you get

- **Eight built-in providers**: YouTube, Vimeo, Spotify, SoundCloud, X, TikTok, Bluesky and Flickr. None needs an
  account or an access token. You can [add your own](extending/custom-providers.md).
- **Safe rendering**: markup is rebuilt from an allowlist, never passed through (see
  [How embeds are rendered](security/rendering.md)).
- **Stored answers**: answers are kept between lookups, in the cache or a database table, and failed lookups are
  remembered too, so a deleted video isn't asked about on every page view (see [Stores](usage/stores.md)).
- **Pooled lookups**: `resolveMany()` looks up every URL it hasn't stored in one pooled HTTP request.
- **Optional discovery**: sites that aren't providers can still be embedded when their pages name an oEmbed
  endpoint, behind a guard that keeps lookups off private networks (see [Discovery](security/discovery.md)).
- **Optional Filament components**: a form field with a live preview, an infolist entry and a table column (see
  [Filament](filament/_index.md)).

## Requirements

- PHP 8.3 or later
- Laravel 12 or 13
- Filament 5, only for the form field, infolist entry and table column

## Where to go next

- [Installation](installation.md) covers Composer, the config, the database store and the stylesheets.
- [Looking up and rendering URLs](usage/embedder.md) covers the `OEmbed` facade and the `Embedder` it returns.
- [Custom providers](extending/custom-providers.md) shows how to embed a site that isn't built in.

If you use [Rich Editor Plus](docs:rich-editor-plus/introduction), the
[Rich Editor Plus Embed](docs:rich-editor-plus-embed/introduction) plugin builds on this package to add embeds to
the editor.
