---
title: Custom stores
weight: 30
description: Keep oEmbed answers anywhere by implementing EmbedStore and registering it with OEmbed::extend().
---

The package ships a `cache` and a `database` store (see [Stores](../usage/stores.md)). To keep answers somewhere
else, implement `Webwizardsusa\OEmbed\Stores\EmbedStore` and register it under a name.

## The interface

```php
namespace Webwizardsusa\OEmbed\Stores;

use Webwizardsusa\OEmbed\OEmbedResponse;

interface EmbedStore
{
    /**
     * @param  array<string>  $keys
     * @return array<string, OEmbedResponse|false>
     */
    public function getMany(array $keys): array;

    public function put(string $key, string $url, ?OEmbedResponse $response): void;

    public function forget(string $key): void;
}
```

### `getMany(array $keys)`

Returns what is stored for the keys, keyed by key, **leaving out keys that aren't stored or have expired**:

- an `OEmbedResponse` for a stored answer;
- `false` for a stored failed lookup.

A key left out is looked up again; a key answering `false` isn't, until it expires. The keys may contain duplicates.
Lookups for a whole page go through one `getMany()` call, so make it a single round trip where your backend allows.

### `put(string $key, string $url, ?OEmbedResponse $response)`

Keeps an answer, or a failed lookup when `$response` is `null`, replacing whatever the key held. `$url` is the URL
that was looked up, for stores that want to keep it (the database store does); you don't need it to read answers
back.

Keep answers for the manager's `ttl()` and failures for its `failureTtl()` (see [Expiry](#expiry)).

### `forget(string $key)`

Removes what the key holds, if anything.

## Store keys

Keys come from `Webwizardsusa\OEmbed\Stores\StoreKey::make($provider, $url, $maxWidth)`: a 40-character SHA-1 of the
provider's name (`discovered` for discovery), the URL and the `maxwidth` it was asked for with, since providers
answer differently for each. Store them as they are.

## Keeping answers: `toArray()` and `fromArray()`

Store an answer with `$response->toArray()`, which writes it in oEmbed's own key names, and read it back with
`OEmbedResponse::fromArray($data)`. `fromArray()` checks every value again and returns `null` for data that isn't a
usable answer; leave such keys out of `getMany()`'s result so they are looked up again.

## Expiry

The `EmbedStoreManager` holds the times from the config, in seconds:

- `ttl()`: how long to keep an answer (`oembed.ttl`).
- `failureTtl()`: how long to keep a failed lookup (`oembed.failure_ttl`).

Read them when you create the store, as below, so your store follows the config like the built-in ones.

## Example: a Redis store

```php
namespace App\OEmbed;

use Illuminate\Redis\Connections\Connection;
use Webwizardsusa\OEmbed\OEmbedResponse;
use Webwizardsusa\OEmbed\Stores\EmbedStore;

class RedisEmbedStore implements EmbedStore
{
    /**
     * Stored for a failed lookup; anything else is an answer as JSON.
     */
    protected const string FAILED = 'failed';

    public function __construct(
        protected Connection $redis,
        protected int $ttl,
        protected int $failureTtl,
        protected string $prefix = 'oembed:',
    ) {}

    public function getMany(array $keys): array
    {
        $keys = array_values(array_unique($keys));

        if ($keys === []) {
            return [];
        }

        $values = $this->redis->mget(array_map(fn (string $key): string => $this->prefix.$key, $keys));
        $found = [];

        foreach ($keys as $index => $key) {
            $value = $values[$index] ?? null;

            // A missing key is null (Predis) or false (PhpRedis): leave it out.
            if (! is_string($value)) {
                continue;
            }

            if ($value === self::FAILED) {
                $found[$key] = false;

                continue;
            }

            $data = json_decode($value, true);
            $response = is_array($data) ? OEmbedResponse::fromArray($data) : null;

            if ($response !== null) {
                $found[$key] = $response;
            }
        }

        return $found;
    }

    public function put(string $key, string $url, ?OEmbedResponse $response): void
    {
        $response === null
            ? $this->redis->setex($this->prefix.$key, $this->failureTtl, self::FAILED)
            : $this->redis->setex($this->prefix.$key, $this->ttl, json_encode($response->toArray(), JSON_THROW_ON_ERROR));
    }

    public function forget(string $key): void
    {
        $this->redis->del($this->prefix.$key);
    }
}
```

Redis expires the keys itself, so this store needs no pruning.

## Registering a store

Register the store under a name with `OEmbed::extend()`, in a service provider's `boot()` method. The closure gets
the container and returns the store:

```php
use App\OEmbed\RedisEmbedStore;
use Illuminate\Contracts\Foundation\Application;
use Webwizardsusa\OEmbed\Facades\OEmbed;
use Webwizardsusa\OEmbed\Stores\EmbedStoreManager;

public function boot(): void
{
    OEmbed::extend('redis', function (Application $app): RedisEmbedStore {
        $manager = $app->make(EmbedStoreManager::class);

        return new RedisEmbedStore(
            $app->make('redis')->connection('cache'),
            $manager->ttl(),
            $manager->failureTtl(),
        );
    });
}
```

Then select it for the whole app:

```env
OEMBED_STORE=redis
```

or for one embedder, with `->store('redis')`.

> [!IMPORTANT]
> Call `extend()` before the store is first used. The manager creates each store once and keeps it, so extending a
> name after it has been used in the same request has no effect.

The closure runs once, the first time the store is needed. Registering a store under `cache` or `database` replaces
the built-in one; note that `oembed:prune` then refuses to run, since it only prunes the built-in database store.

## Using a store instance directly

For a one-off, such as a store in a test or a job, you can skip registration and pass an instance:

```php
OEmbed::store(new RedisEmbedStore($connection, 3600, 60))->resolve($url);
```
