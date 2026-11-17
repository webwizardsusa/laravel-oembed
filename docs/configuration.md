---
title: Configuration
weight: 30
description: Every key in config/oembed.php, its default and what it changes.
---

The config file holds the app's defaults. Publish it with:

```bash
php artisan vendor:publish --tag=oembed-config
```

Every `Embedder` starts from these values when it is created, and each can change them for itself with its
[fluent methods](usage/embedder.md#changing-settings). There is no config key for the provider list; see
[App-wide providers and defaults](extending/app-defaults.md) to change it for the whole app.

## Overview

| Key | Default | Environment variable |
|---|---|---|
| `store` | `'cache'` | `OEMBED_STORE` |
| `ttl` | `604800` (a week) | |
| `failure_ttl` | `600` (ten minutes) | |
| `cache.store` | `null` (the default cache store) | `OEMBED_CACHE_STORE` |
| `database.connection` | `null` (the default connection) | `OEMBED_DB_CONNECTION` |
| `database.table` | `'oembed_responses'` | |
| `timeout` | `3` | |
| `max_width` | `800` | |
| `discovery` | `false` | |
| `privacy_enhanced_youtube` | `false` | |

## `store`

```php
'store' => env('OEMBED_STORE', 'cache'),
```

Where answers are kept between lookups: `cache`, `database`, or the name of a store you added with
`OEmbed::extend()` (see [Custom stores](extending/custom-stores.md)). The `database` store needs its migration first
(see [Installation](installation.md#use-the-database-store-optional)).

## `ttl` and `failure_ttl`

```php
'ttl' => 60 * 60 * 24 * 7,

'failure_ttl' => 60 * 10,
```

How long, in seconds, an answer is kept, and how long a failed lookup is remembered before the URL is asked about
again. Every store uses both, including stores you add. A failed lookup is any answer that isn't usable oEmbed: an
error status, a timeout, an answer that is too large, or one without the markup its type needs.

Keeping failures matters: without it, a page linking to a deleted video would ask the provider about it on every view.

## `cache.store`

```php
'cache' => [
    'store' => env('OEMBED_CACHE_STORE'),
],
```

The Laravel cache store the `cache` store writes to, by name from `config/cache.php`. `null` uses your default store.
Keys are prefixed with `oembed:`.

## `database.connection` and `database.table`

```php
'database' => [
    'connection' => env('OEMBED_DB_CONNECTION'),
    'table' => 'oembed_responses',
],
```

The connection (`null` for the default one) and the table of the `database` store. The published migration reads both
when it runs, so set them before migrating.

## `timeout`

```php
'timeout' => 3,
```

Seconds to wait for a provider's oEmbed endpoint, and for each request discovery makes. It is used both as the
connection timeout and the total request timeout. Keep it short: lookups happen while a page is rendering, the first
time a URL is seen.

## `max_width`

```php
'max_width' => 800,
```

The `maxwidth` sent to providers, in pixels. Providers use it to size the player they answer with. Set it to `null`
to send none. Answers are stored per `maxwidth`, so a different value means a new lookup.

## `discovery`

```php
'discovery' => false,
```

Whether embedders use oEmbed discovery for URLs no provider takes. It is off by default because the server then
fetches pages that people link to. Read [Discovery](security/discovery.md) before turning it on.

## `privacy_enhanced_youtube`

```php
'privacy_enhanced_youtube' => false,
```

Plays YouTube videos from `www.youtube-nocookie.com`, which sets no cookies until the video is played. Only the
player's host changes; lookups still go to YouTube's endpoint.
