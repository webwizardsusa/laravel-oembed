---
title: Stores
weight: 40
description: Where answers are kept between lookups, how long, and how to prune the database store.
---

Every lookup's result is kept in a store, so a URL is asked about once and then read back on later page views. Failed
lookups are kept too, for a shorter time, so a deleted or private video isn't asked about on every view.

| | Kept for | Config key |
|---|---|---|
| An answer | a week | `oembed.ttl` |
| A failed lookup | ten minutes | `oembed.failure_ttl` |

Both times apply to every store, including ones you add.

## The built-in stores

### `cache` (the default)

Keeps answers in a Laravel cache store: the one named by `OEMBED_CACHE_STORE` (`oembed.cache.store`), or your default
store. Keys start with `oembed:`. Nothing to set up.

The cache is the simplest choice, but clearing it (`php artisan cache:clear`, a deploy that flushes Redis) means every
URL is looked up again.

### `database`

Keeps answers in a table, `oembed_responses` by default, one row per stored lookup:

| Column | |
|---|---|
| `key` | The [store key](../extending/custom-stores.md#store-keys), unique. |
| `url` | The URL that was looked up. |
| `response` | The answer as JSON, or `null` for a failed lookup. |
| `expires_at` | When the row stops being used. |

Set it up as described in [Installation](../installation.md#use-the-database-store-optional), then select it:

```env
OEMBED_STORE=database
```

Rows past their `expires_at` are ignored when reading and replaced on the next lookup, but they aren't deleted until
you prune them.

## Pruning the database store

```bash
php artisan oembed:prune
```

Deletes the `database` store's expired rows and prints how many it deleted. Schedule it daily when you use that store:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('oembed:prune')->daily();
```

The command always prunes the `database` store, whatever `OEMBED_STORE` says. If you replaced the `database` store
with one of your own through `OEmbed::extend('database', …)`, it fails with an error instead, since it only knows how
to prune the built-in one. The `cache` store needs no pruning: the cache expires entries itself.

## Choosing a store per lookup

`store()` on an embedder overrides the default for that embedder. It takes a store name or an `EmbedStore`
instance:

```php
use Webwizardsusa\OEmbed\Facades\OEmbed;

OEmbed::store('database')->render($url);

OEmbed::store(new MyEmbedStore)->resolve($url);
```

In Filament components, there's no `store()` method; set it through the embedder:

```php
use Webwizardsusa\OEmbed\Embedder;
use Webwizardsusa\OEmbed\Filament\Forms\Components\OEmbedInput;

OEmbedInput::make('video_url')->embedder(fn (Embedder $embedder) => $embedder->store('database'));
```

## Clearing a stored answer

Answers are stored per provider, URL and `maxwidth`. To look a URL up again before its answer expires, forget its
key in the store:

```php
use Webwizardsusa\OEmbed\Embedder;
use Webwizardsusa\OEmbed\Stores\StoreKey;

$embedder = Embedder::make();
$provider = $embedder->getProviders()->match($url);

if ($provider !== null) {
    $embedder->getStore()->forget(StoreKey::make($provider->getName(), $url, $embedder->getMaxWidth()));
}
```

Use the trimmed URL, as the embedder does. A URL found by discovery is stored under the provider name `discovered`.

## Your own store

To keep answers elsewhere, such as in Redis directly or in a document store, implement `EmbedStore` and register it
with `OEmbed::extend()`. See [Custom stores](../extending/custom-stores.md).
