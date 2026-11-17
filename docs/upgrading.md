---
title: Upgrading
weight: 90
description: Upgrading from 0.1 to 1.0, a rewrite with a new API.
---

## From 0.1 to 1.0

1.0 is a rewrite. The package name, the `Webwizardsusa\OEmbed` namespace, the `oembed` config file and the facade's
name stay, but the classes, the config keys and the provider format are new, so code written for 0.1 needs changes.
Answers cached by 0.1 aren't read by 1.0 and are fetched again.

### Requirements

1.0 needs PHP 8.3 or newer and Laravel 12 or 13. 0.1 supported Laravel 10 and 11.

### The facade

The facade moved from `Webwizardsusa\OEmbed\Facade\OEmbed` to `Webwizardsusa\OEmbed\Facades\OEmbed`, and the service
is now an [`Embedder`](usage/embedder.md) instead of the `Webwizardsusa\OEmbed\OEmbed` class.

| 0.1 | 1.0 |
|---|---|
| `OEmbed::fromUrl($url)` returning an `OEmbedResponse` or `null` | `OEmbed::resolve($url)` returning an [`Embed`](usage/embedder.md#the-embed-object) or `null` |
| `fromUrl($url)->render()` | `OEmbed::render($url)`, or `{{ $embed }}` in Blade |
| `fromUrl($url, throw: true)` | `resolve()` and `render()` don't throw for URLs that can't be embedded; check for `null` |
| `fromUrl($url, useCache: false)` | Answers are always kept in a [store](usage/stores.md) |
| `OEmbed::register(...)` | `OEmbed::withProviders([...])` |
| `OEmbed::get($name)`, `has($name)`, `all()` | `OEmbed::getProviders()` |

In Blade, the [`<x-oembed>`](usage/blade-component.md) component replaces calling `render()` yourself.

### The response

`OEmbedResponse` is now a read-only object of the provider's checked answer, with typed properties instead of
methods; see [The `OEmbedResponse` object](usage/embedder.md#the-oembedresponse-object). The URL and the provider
moved to `Embed`.

| 0.1 | 1.0 |
|---|---|
| `getUrl()` | `$embed->url` |
| `getProvider()` | `$embed->providerName()` |
| `getWidth()`, `getHeight()` | `$embed->response->width`, `$embed->response->height` |
| `get('title')`, `raw()` | `$embed->title()`, `$embed->response->...` |
| `embedCode()` | `$embed->toHtml()` |
| `width()`, `height()`, `setEmbedCode()` | Set the size with `OEmbed::maxWidth()`; the markup is rebuilt from the answer and can't be replaced |
| `aspectRatio()`, `retrievedAt()` | Removed |
| `toArray()` | `$embed->toArray()`, with the keys `url`, `provider`, `title` and `thumbnail` |

> [!WARNING]
> 0.1 printed the provider's `html` as it came. 1.0 never does: it rebuilds the markup, allowing only the iframes and
> scripts each provider declares. See [How embeds are rendered](security/rendering.md).

### Views and CSS

The `html`, `iframe` and `responsive-iframe` views are gone, and so are the `.oembed-responsive-container` rules you
added to your stylesheet. Embeds render as a `<figure class="oembed">`, styled by
[`OEmbed::stylesheet()`](installation.md#include-the-stylesheet); the classes are listed in
[Styling](extending/styling.md).

### Configuration

Republish `config/oembed.php` and copy over any values you changed. The keys map like this:

| 0.1 | 1.0 |
|---|---|
| `providers` | No config key; see [App-wide providers and defaults](extending/app-defaults.md) |
| `cache.enabled` (`OEMBED_ENABLE_CACHE`) | Removed: answers are always stored |
| `cache.ttl` (`OEMBED_URL_CACHE_TTL`), in minutes | `ttl`, in **seconds** |
| `cache.invalid_ttl` (`OEMBED_INVALID_URL_CACHE_TTL`), in minutes | `failure_ttl`, in **seconds** |
| `cache.key_prefix` (`OEMBED_CACHE_PREFIX`) | Removed |
| `cache.driver` (`OEMBED_CACHE_DRIVER`) | `cache.store` (`OEMBED_CACHE_STORE`) |

1.0 adds a [database store](usage/stores.md), discovery, `max_width`, `timeout` and `privacy_enhanced_youtube`; see
[Configuration](configuration.md).

### Providers

The built-in providers are YouTube, Vimeo, Spotify, SoundCloud, X, TikTok, Bluesky and Flickr.

- `twitter` is now named `x`.
- Amazon, Dailymotion, Pinterest, Scribd and Tumblr aren't built in any more. Add the ones you need as
  [custom providers](extending/custom-providers.md).

Provider classes extending `AbstractOembedProvider` with `$regexes` and `$oembedUrl` become
[`Provider::make()`](extending/custom-providers.md) definitions: URL patterns instead of regexes, the endpoint, and the
iframe hosts or script the markup may load. Overriding `pull()` has no direct replacement; see
[Overriding behaviour](extending/app-defaults.md#overriding-behaviour).

### Exceptions

`OEmbedException`, `InvalidOembedResponse`, `OembedNotFoundException` and `ProviderNotFoundException` are removed.
A URL without a provider, a failed request and an unusable answer all resolve to `null`, and `render()` prints a link
instead of an embed.
