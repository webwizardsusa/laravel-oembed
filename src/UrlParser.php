<?php

namespace Webwizardsusa\OEmbed;

use Illuminate\Cache\Repository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Webwizardsusa\OEmbed\Exceptions\OEmbedException;

class UrlParser
{

    protected OEmbed $oembed;
    protected Repository|\Illuminate\Contracts\Cache\Repository|null $cache;

    public function __construct(OEmbed $oembed) {

        $this->oembed = $oembed;

        if (config('oembed.cache.enabled')) {
            $this->cache = Cache::store(config('oembed.cache.driver'));
        }
    }

    public function cache():Repository|\Illuminate\Contracts\Cache\Repository|null {
        return $this->cache;
    }


    public static function make(): static
    {
        return app(static::class);
    }


    public function makeCacheKey(OEmbedUrl|string $url): string
    {
        $url = $url instanceof OEmbedUrl ? $url : new OEmbedUrl($url);
        return config('oembed.cache.key_prefix') . md5($url->url());
    }
    protected function cacheSuccessful(OEmbedUrl $url, OEmbedResponse $response): void {
        $cacheTtl = config('oembed.cache.ttl');
        if (!$cacheTtl || !$this->cache) {
            return;
        }
        $cacheKey = $this->makeCacheKey($url);
        cache()->set($cacheKey, $response->toArray(), Carbon::now()->addMinutes($cacheTtl));
    }

    protected function cacheInvalid(OEmbedUrl $url): void {
        $cacheTtl = config('oembed.invalid_cache_ttl', config('oembed.cache.ttl'));
        if (!$cacheTtl || !$this->cache) {
            return;
        }
        $cacheKey = $this->makeCacheKey($url);
        cache()->set($cacheKey, false, Carbon::now()->addMinutes($cacheTtl));
    }

    public function retrieve(string|OEmbedUrl $url, bool $useCache = true, bool $throw = false): ?OEmbedResponse
    {
        $url = $url instanceof OEmbedUrl ? $url : new OEmbedUrl($url);
        $cacheKey = $this->makeCacheKey($url);

        if ($useCache && $this->cache && cache()->has($cacheKey)) {
            $cache = cache()->get($cacheKey);
            return $cache ? OEmbedResponse::hydrate($cache) : null;
        }
        foreach ($this->oembed->all() as $provider) {
            try {
                $results = $provider->extract($url);
                if ($results) {

                    if ($useCache) {
                        $this->cacheSuccessful($url, $results);
                    }
                    return $results;
                }


            } catch(\Exception $e) {

                if ($e instanceof OembedException) {
                    if ($throw) {
                        throw($e);
                    }
                    if ($useCache) {
                        $this->cacheInvalid($url);
                    }
                }
            }

        }
        if ($useCache) {
            $this->cacheInvalid($url);
        }

        return null;
    }
}
