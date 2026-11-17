---
title: Blade component
weight: 20
description: Render an embed in a Blade view with the x-oembed component.
---

## Usage

```blade
<x-oembed :url="$post->video_url" />

<x-oembed :url="$post->video_url" caption="Watch this" />
```

The component prints the same markup as [`OEmbed::render()`](embedder.md#rendering): a `<figure class="oembed">` with
the provider's player, post or photo, or the link when there is nothing to embed. A `null` URL, or one that isn't an
absolute `http(s)` URL, prints nothing, so you don't need an `@if` around it for optional fields.

Include the stylesheet once on the page:

```blade
<style>{!! OEmbed::stylesheet() !!}</style>
```

## Attributes

| Attribute | Type | |
|---|---|---|
| `url` | `?string` | The URL to embed. |
| `caption` | `?string` | Shown in a `<figcaption>`, escaped. Left out when blank. |
| `embedder` | `?Embedder` | The embedder to render with. Defaults to a new one with the app's defaults. |

## Other settings

Pass `:embedder` to render with settings other than the app's defaults. The facade's fluent methods return an
`Embedder`, so you can build one inline:

```blade
<x-oembed :url="$post->video_url" :embedder="OEmbed::privacyEnhancedYouTube()->maxWidth(640)" />
```

When several views need the same settings, build the embedder once, in a view composer or the controller, and pass it
along:

```php
use Webwizardsusa\OEmbed\Facades\OEmbed;

return view('posts.show', [
    'post' => $post,
    'embedder' => OEmbed::discovery()->store('database'),
]);
```

```blade
<x-oembed :url="$post->video_url" :embedder="$embedder" />
```

## Many embeds on one page

Each `<x-oembed>` looks its URL up on its own. Stored answers are read from the store, but on a page with many URLs
that haven't been seen yet, each one is a separate request. Resolve them together first with
[`resolveMany()`](embedder.md#many-urls-at-once), which stores every answer in one pooled request; the components then
read them from the store:

```blade
@php(OEmbed::resolveMany($posts->pluck('video_url')))

@foreach ($posts as $post)
    <x-oembed :url="$post->video_url" />
@endforeach
```

The embedder you pre-resolve with must have the same providers and `maxWidth()` as the one the components use, since
answers are stored per provider and `maxwidth`.
