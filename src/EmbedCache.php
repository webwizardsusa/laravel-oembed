<?php

namespace Webwizardsusa\OEmbed;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

/**
 * oEmbed answers by provider, URL and maximum width. A failed lookup is kept too, for a shorter time, so a deleted
 * video isn't asked about on every page view.
 */
class EmbedCache
{
    protected const string PREFIX = 'oembed:';

    public function __construct(
        protected Repository $store,
        protected int $ttl,
        protected int $failureTtl,
    ) {}

    public static function fromConfig(): static
    {
        return new static(
            Cache::store(config('oembed.cache.store')),
            (int) config('oembed.cache.ttl', 60 * 60 * 24 * 7),
            (int) config('oembed.cache.failure_ttl', 60 * 10),
        );
    }

    public static function key(string $provider, string $url, ?int $maxWidth): string
    {
        return static::PREFIX.sha1("{$provider}|{$url}|{$maxWidth}");
    }

    /**
     * @param  array<string>  $keys
     * @return array<string, OEmbedResponse|false> Only the keys found: an answer, or false for a lookup that failed.
     */
    public function getMany(array $keys): array
    {
        if ($keys === []) {
            return [];
        }

        $found = [];

        foreach ($this->store->many(array_values(array_unique($keys))) as $key => $value) {
            if ($value === false) {
                $found[$key] = false;
            } elseif (is_array($value) && ($response = OEmbedResponse::fromArray($value)) !== null) {
                $found[$key] = $response;
            }
        }

        return $found;
    }

    public function put(string $key, ?OEmbedResponse $response): void
    {
        $response === null
            ? $this->store->put($key, false, $this->failureTtl)
            : $this->store->put($key, $response->toArray(), $this->ttl);
    }
}
