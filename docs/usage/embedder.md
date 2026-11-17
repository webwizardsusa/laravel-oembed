---
title: Looking up and rendering URLs
weight: 10
description: The OEmbed facade, the Embedder it returns, and the Embed and OEmbedResponse objects.
---

## The facade and the embedder

Everything goes through an `Webwizardsusa\OEmbed\Embedder`. The `OEmbed` facade gives you a **new one each call**,
with the app's defaults from `config/oembed.php`:

```php
use Webwizardsusa\OEmbed\Facades\OEmbed;

OEmbed::discovery()->render($url);   // discovery for this call only
OEmbed::render($otherUrl);           // a fresh embedder: discovery is back to the config default
```

So a fluent method never changes the settings of another lookup. To reuse settings, keep the embedder in a variable:

```php
$embedder = OEmbed::maxWidth(640)->privacyEnhancedYouTube();

$embedder->render($first);
$embedder->render($second);
```

`Embedder::make()` does the same as the facade, and resolves the embedder from the container. That is what lets you
[change the defaults app-wide](../extending/app-defaults.md).

## Rendering

```php
OEmbed::render('https://www.youtube.com/watch?v=dQw4w9WgXcQ', caption: 'Watch this');
```

`render(?string $url, ?string $caption = null)` returns an `Illuminate\Support\HtmlString`:

```html
<figure class="oembed" data-kind="video">
  <div class="oembed-frame" style="aspect-ratio: 640 / 360">
    <iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ?feature=oembed" width="640" height="360" title="…"
            allow="…" allowfullscreen="" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>
  </div>
  <figcaption>Watch this</figcaption>
</figure>
```

- **Players** (YouTube, Vimeo, Spotify, SoundCloud, TikTok) become the provider's `<iframe>` with a fixed set of
  attributes. `data-kind` is `video` or `rich`.
- **Posts** (X, Bluesky) become the provider's `<blockquote>`, kept to text and links, followed by the provider's own
  script. `data-kind` is `rich`.
- **Photos** (Flickr) become an `<img>` linking to the photo's page. `data-kind` is `photo`.
- **Anything else**, including a failed lookup or a URL no provider takes, renders as a link inside the figure, with
  the answer's title as its text when there is one. There is no `data-kind` then.
- A URL that isn't an absolute `http(s)` URL (or `null`) renders an empty string.

The caption is escaped and left out when blank. The exact rules are in
[How embeds are rendered](../security/rendering.md).

Include `OEmbed::stylesheet()` on pages that show embeds (see [Installation](../installation.md#include-the-stylesheet)).

## Looking a URL up

`resolve(string $url)` returns an `Webwizardsusa\OEmbed\Embed`, or `null` when no provider takes the URL or there is
nothing to embed for it:

```php
$embed = OEmbed::resolve($url);

if ($embed !== null) {
    $embed->title();          // ?string
    $embed->thumbnail();      // ?string, an http(s) URL
    $embed->providerName();   // ?string, such as "youtube"; null for a page found by discovery
    $embed->providerLabel();  // ?string, such as "YouTube", or what a discovered page calls its provider
    $embed->toHtml();         // the same markup as OEmbed::render($url)
}
```

`resolve()` trims the URL first. Answers are read from the [store](stores.md) when they are there, and the lookup
is stored, failure included.

### Many URLs at once

`resolveMany(iterable $urls)` looks up a list of URLs together: stored answers first, then every other URL a provider
takes in one pooled request (up to ten at a time). Use it whenever you show several embeds on a page, such as a list
of posts:

```blade
@php($embeds = OEmbed::resolveMany($posts->pluck('video_url')))

@foreach ($posts as $post)
    {{-- An Embed is Htmlable, so Blade prints its markup --}}
    {{ $embeds[trim((string) $post->video_url)] ?? '' }}
@endforeach
```

The result is keyed by the **trimmed** URL. URLs nothing takes, and values that aren't strings, are left out of the
result; URLs whose lookup failed are present with `null`. With discovery on, at most five uncached pages are
discovered per call (see [Discovery](../security/discovery.md)).

### Whether a URL can be looked up

`takes(mixed $url)` answers whether the embedder would look the URL up at all, without making a request: an
absolute `http(s)` URL of at most 2048 characters that a provider takes, or, with discovery on, any such URL.

```php
OEmbed::takes('https://vimeo.com/76979871');   // true
OEmbed::takes('https://example.com/page');     // false, unless discovery is on
```

A URL that is taken may still have nothing to embed: the video may be private or deleted. Only `resolve()` finds out.

## Changing settings

Each method returns the embedder, so they chain. They change only that embedder.

| Method | What it does |
|---|---|
| `providers(array $providers)` | Embeds from these `Provider`s instead of the built-in ones. |
| `withProviders(array $providers)` | Adds providers. One with the name of a provider already in the list replaces it. |
| `withoutProviders(array $names)` | Removes providers by name, such as `['tiktok']`. |
| `maxWidth(?int $width)` | The `maxwidth` sent to providers. `null` sends none. |
| `privacyEnhancedYouTube(bool $condition = true)` | Plays YouTube from `www.youtube-nocookie.com`. |
| `discovery(bool $condition = true)` | Embeds URLs no provider takes through oEmbed discovery. |
| `store(EmbedStore\|string\|null $store)` | Keeps answers in another store: a name (`cache`, `database`, or one you added) or an `EmbedStore` instance. `null` uses the default. |

```php
use Webwizardsusa\OEmbed\Facades\OEmbed;
use Webwizardsusa\OEmbed\Providers\Provider;

OEmbed::withoutProviders(['tiktok'])
    ->withProviders([
        Provider::make('loom')
            ->label('Loom')
            ->urls(['*.loom.com/share/*'])
            ->endpoint('https://www.loom.com/v1/oembed')
            ->iframe(['www.loom.com']),
    ])
    ->maxWidth(640)
    ->store('database')
    ->render($url);
```

The providers are listed in [Built-in providers](providers.md), and writing your own is covered in
[Custom providers](../extending/custom-providers.md).

The embedder also has getters for its current settings: `getProviders()` (a `ProviderRegistry`), `getMaxWidth()`,
`isPrivacyEnhancedYouTube()`, `discovers()` and `getStore()`.

## The `Embed` object

`Embed` is a read-only object for a URL that resolved to something embeddable. It implements `Htmlable`, so Blade
prints it with `{{ $embed }}`.

| Member | Type | |
|---|---|---|
| `url` | `string` | The trimmed URL. |
| `provider` | `?Provider` | The provider that took it, or `null` for a discovered page. |
| `response` | `OEmbedResponse` | The checked answer. |
| `title()` | `?string` | The answer's title. |
| `thumbnail()` | `?string` | The answer's thumbnail URL. |
| `providerName()` | `?string` | The provider's name, such as `youtube`. |
| `providerLabel()` | `?string` | The provider's label, such as `YouTube`, or the answer's `provider_name` for a discovered page. |
| `toHtml()` | `string` | The rendered figure, without a caption. |
| `toArray()` | `array` | `['url' => …, 'provider' => …, 'title' => …, 'thumbnail' => …]`, handy for JSON responses. |

## The `OEmbedResponse` object

`Webwizardsusa\OEmbed\OEmbedResponse` holds the parts of a provider's answer the package uses. Every value is checked
when the answer is read: text is trimmed and cut to 500 characters, URLs must be absolute `http(s)`, and sizes must be
whole numbers from 1 to 10,000. Anything else becomes `null`.

| Property | Type | oEmbed field |
|---|---|---|
| `type` | `string` | `type`: `photo`, `video`, `rich` or `link` |
| `title` | `?string` | `title` |
| `authorName` | `?string` | `author_name` |
| `authorUrl` | `?string` | `author_url` |
| `providerName` | `?string` | `provider_name` |
| `thumbnailUrl` | `?string` | `thumbnail_url` |
| `thumbnailWidth`, `thumbnailHeight` | `?int` | `thumbnail_width`, `thumbnail_height` |
| `width`, `height` | `?int` | `width`, `height` |
| `html` | `?string` | `html`, for `video` and `rich` only |
| `url` | `?string` | `url`, for `photo` only |

> [!WARNING]
> `html` is kept exactly as the provider sent it. Never print it. Render through `Embed::toHtml()` or
> `Embedder::render()`, which rebuild the markup they allow.

`OEmbedResponse::fromArray(array $data)` reads an answer in oEmbed's own key names and returns `null` when it isn't
usable: an unknown type, a `photo` without a `url`, or a `video` or `rich` answer without `html`. `toArray()` writes it
back in the same key names, which is how stores keep it.

To render an answer you already have, such as one you stored yourself, use
`renderAnswer(string $url, OEmbedResponse $answer, ?string $caption = null)` on an embedder.
