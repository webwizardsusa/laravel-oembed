---
title: Custom providers
weight: 10
description: Embed a site that isn't built in by declaring its URLs, endpoint and what its markup may load.
---

A provider is a `Webwizardsusa\OEmbed\Providers\Provider`: the URLs it answers for, its oEmbed endpoint, and what its
markup is allowed to load. Find the site's oEmbed endpoint in its developer docs, or in the
`<link rel="alternate" type="application/json+oembed">` of one of its pages.

## A player provider

Most video and audio sites answer with an `<iframe>`. Name the hosts that iframe may come from:

```php
use Webwizardsusa\OEmbed\Facades\OEmbed;
use Webwizardsusa\OEmbed\Providers\Provider;

$loom = Provider::make('loom')
    ->label('Loom')
    ->urls(['*.loom.com/share/*'])
    ->endpoint('https://www.loom.com/v1/oembed')
    ->iframe(['www.loom.com']);

OEmbed::withProviders([$loom])->render('https://www.loom.com/share/abc123');
```

## A post provider

Sites whose embeds are a `<blockquote>` turned into a widget by a script, as X and Bluesky do, declare that script
instead (`acme.social` stands for the site you add):

```php
use Webwizardsusa\OEmbed\Providers\Provider;

Provider::make('acme-social')
    ->label('Acme Social')
    ->urls(['acme.social/*/posts/*'])
    ->endpoint('https://acme.social/oembed')
    ->script('https://acme.social/widgets.js');
```

Check what the provider actually answers before choosing: if its `html` is an iframe, use `iframe()`; if it is a
blockquote plus a script, use `script()`.

## Methods

### `make(string $name)`

The provider's name: lowercase letters, digits and dashes, starting with a letter or digit (`loom`, `my-video-host`).
Anything else throws an `InvalidArgumentException`.

The name identifies the provider: `withProviders()` replaces a provider with the same name, `withoutProviders()`
removes by name, and answers are [stored](../usage/stores.md) under it. Don't name a provider `discovered`, which is
the name answers found by discovery are stored under.

### `label(string $label)`

The name shown to people, such as in the Filament preview card. Defaults to the name in headline case
(`my-video-host` becomes "My Video Host").

### `urls(array $patterns)`

The URLs the provider takes, as `host/path` patterns:

- A host starting with `*.` matches that domain and every subdomain: `*.loom.com` takes `loom.com` and
  `www.loom.com`. That is the only place a `*` may appear in a host; anything else throws.
- `*` in the path matches anything, including `/`. A pattern without a path, such as `example.com`, takes every path.
- The query string isn't matched, and hosts are compared without case.

A URL is only matched when it is an absolute `http(s)` URL without credentials, of at most 2048 characters.

### `endpoint(string $url, array $query = [])`

The oEmbed endpoint. It must be `https`, or it throws. Each lookup sends a `GET` with `url`, `format=json` and
`maxwidth` (unless it is `null`), plus anything in `$query`, such as an access token:

```php
Provider::make('acme-video')
    ->urls(['*.acme-video.com/v/*'])
    ->endpoint('https://api.acme-video.com/oembed', [
        'access_token' => config('services.acme_video.token'),
    ])
    ->iframe(['player.acme-video.com']);
```

The client asks only the endpoint, never the URL being embedded, and doesn't follow redirects, so use the endpoint's
final address. The answer must be JSON. A provider without an endpoint is never looked up.

### `iframe(array $hosts)`

The hosts the provider's player may load from, compared exactly (no wildcards). An iframe from any other host in an
answer is refused, and the embed shows as its link. List every host the provider uses, such as
`['www.youtube.com', 'www.youtube-nocookie.com']`.

### `script(string $url)`

The script that turns the provider's `<blockquote>` into an embed. It must be `https`, or it throws. It is always
taken from here, never from an answer.

### `sandboxIframes(bool $condition = true)`

Plays the provider's iframes with a `sandbox` attribute: scripts, same-origin access, popups and presentation are
allowed, but the player can't navigate your page. Use it for providers you trust less. Some players need more than
the sandbox allows, so test the embed after turning it on.

## Will it render?

Declaring a provider decides which URLs are looked up. What is rendered depends on the answer and on what you
declared:

| The answer is | It renders when |
|---|---|
| `photo` | Always, as an `<img>` linking to the page (the answer needs a photo `url`). |
| `video` or `rich` | The provider has `iframe()` hosts and the answer's first iframe is `https` from one of them; or the provider has no iframe hosts, has a `script()`, and the answer has a `<blockquote>`. |
| `link` | Never: it shows as a link with the answer's title. |

`iframe()` wins when a provider declares both. A provider with neither `iframe()` nor `script()` can only render
photos; its `video` and `rich` answers show as links. Everything else, including a failed lookup, shows as the link.
The full rules are in [How embeds are rendered](../security/rendering.md).

## Using a provider

Per embedder, field or component:

```php
OEmbed::withProviders([$loom])->render($url);

OEmbedInput::make('video_url')->withProviders([$loom]);
```

`withProviders()` adds to the built-in providers; `providers([...])` replaces them. To make a provider available
everywhere, see [App-wide providers and defaults](app-defaults.md).

## Content Security Policy

If your pages send a CSP, add the provider's `iframe()` hosts to `frame-src`, and its `script()` host (plus whatever
that script loads) to `script-src`. See [Content Security Policy](../security/content-security-policy.md).
