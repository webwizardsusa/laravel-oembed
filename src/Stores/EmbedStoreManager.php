<?php

namespace Webwizardsusa\OEmbed\Stores;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Manager;

/**
 * The stores answers can be kept in: `cache` (the default) and `database`, plus any added with `extend()`. The
 * default comes from `oembed.store` (`OEMBED_STORE`).
 *
 * @method EmbedStore driver(?string $driver = null)
 */
class EmbedStoreManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return (string) $this->config->get('oembed.store', 'cache');
    }

    public function store(?string $name = null): EmbedStore
    {
        return $this->driver($name);
    }

    protected function createCacheDriver(): CacheStore
    {
        return new CacheStore(
            Cache::store($this->config->get('oembed.cache.store')),
            $this->ttl(),
            $this->failureTtl(),
        );
    }

    protected function createDatabaseDriver(): DatabaseStore
    {
        return new DatabaseStore(
            DB::connection($this->config->get('oembed.database.connection')),
            (string) $this->config->get('oembed.database.table', 'oembed_responses'),
            $this->ttl(),
            $this->failureTtl(),
        );
    }

    /**
     * Seconds an answer is kept, for every store.
     */
    public function ttl(): int
    {
        return (int) $this->config->get('oembed.ttl', 60 * 60 * 24 * 7);
    }

    /**
     * Seconds a failed lookup is kept, for every store.
     */
    public function failureTtl(): int
    {
        return (int) $this->config->get('oembed.failure_ttl', 60 * 10);
    }
}
