<?php

namespace Webwizardsusa\OEmbed\Stores;

use Webwizardsusa\OEmbed\OEmbedResponse;

/**
 * Where oEmbed answers are kept between lookups. The package ships a `cache` store (the default) and a `database`
 * store; add your own with `OEmbed::extend('name', fn () => new MyStore)` and pick it with `OEMBED_STORE`.
 *
 * Keys come from {@see StoreKey::make()}. A failed lookup is kept too, so a deleted video isn't asked about on every
 * page view: stores keep answers for `oembed.ttl` seconds and failures for `oembed.failure_ttl`.
 */
interface EmbedStore
{
    /**
     * @param  array<string>  $keys
     * @return array<string, OEmbedResponse|false> Only the keys found and not expired: an answer, or false for a
     *                                             lookup that failed.
     */
    public function getMany(array $keys): array;

    /**
     * Keeps an answer, or a failed lookup (null), for the URL it was looked up for.
     */
    public function put(string $key, string $url, ?OEmbedResponse $response): void;

    public function forget(string $key): void;
}
