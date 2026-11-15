<?php

namespace Webwizardsusa\OEmbed\Stores;

use Illuminate\Contracts\Cache\Repository;
use Webwizardsusa\OEmbed\OEmbedResponse;

/**
 * Keeps answers in a Laravel cache store (`oembed.cache.store`, or the default store). The default {@see EmbedStore}.
 */
class CacheStore implements EmbedStore
{
    protected const string PREFIX = 'oembed:';

    public function __construct(
        protected Repository $cache,
        protected int $ttl,
        protected int $failureTtl,
    ) {}

    public function getMany(array $keys): array
    {
        if ($keys === []) {
            return [];
        }

        $found = [];
        $prefixed = array_map(static fn (string $key): string => static::PREFIX.$key, array_values(array_unique($keys)));

        foreach ($this->cache->many($prefixed) as $cacheKey => $value) {
            $key = substr($cacheKey, strlen(static::PREFIX));

            if ($value === false) {
                $found[$key] = false;
            } elseif (is_array($value) && ($response = OEmbedResponse::fromArray($value)) !== null) {
                $found[$key] = $response;
            }
        }

        return $found;
    }

    public function put(string $key, string $url, ?OEmbedResponse $response): void
    {
        $response === null
            ? $this->cache->put(static::PREFIX.$key, false, $this->failureTtl)
            : $this->cache->put(static::PREFIX.$key, $response->toArray(), $this->ttl);
    }

    public function forget(string $key): void
    {
        $this->cache->forget(static::PREFIX.$key);
    }
}
