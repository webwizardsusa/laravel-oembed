<?php

namespace Webwizardsusa\OEmbed\Stores;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Date;
use Webwizardsusa\OEmbed\OEmbedResponse;

/**
 * Keeps answers in a database table (`oembed.database.table`, `oembed_responses` by default), one row per key with
 * the URL, the answer as JSON (null for a failed lookup) and when it expires. Publish and run its migration first:
 * `php artisan vendor:publish --tag=oembed-migrations`. Expired rows are ignored; `php artisan oembed:prune` deletes them.
 */
class DatabaseStore implements EmbedStore
{
    public function __construct(
        protected ConnectionInterface $connection,
        protected string $table,
        protected int $ttl,
        protected int $failureTtl,
    ) {}

    public function getMany(array $keys): array
    {
        if ($keys === []) {
            return [];
        }

        $found = [];

        $rows = $this->query()
            ->whereIn('key', array_values(array_unique($keys)))
            ->where('expires_at', '>', Date::now())
            ->get(['key', 'response']);

        foreach ($rows as $row) {
            if ($row->response === null) {
                $found[$row->key] = false;

                continue;
            }

            $data = json_decode((string) $row->response, true);
            $response = is_array($data) ? OEmbedResponse::fromArray($data) : null;

            if ($response !== null) {
                $found[$row->key] = $response;
            }
        }

        return $found;
    }

    public function put(string $key, string $url, ?OEmbedResponse $response): void
    {
        $now = Date::now();

        $this->query()->upsert([[
            'key' => $key,
            'url' => $url,
            'response' => $response === null ? null : json_encode($response->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
            'expires_at' => $now->copy()->addSeconds($response === null ? $this->failureTtl : $this->ttl),
            'created_at' => $now,
            'updated_at' => $now,
        ]], ['key'], ['url', 'response', 'expires_at', 'updated_at']);
    }

    public function forget(string $key): void
    {
        $this->query()->where('key', $key)->delete();
    }

    /**
     * Deletes expired rows.
     *
     * @return int How many were deleted.
     */
    public function prune(): int
    {
        return $this->query()->where('expires_at', '<=', Date::now())->delete();
    }

    protected function query(): Builder
    {
        return $this->connection->table($this->table);
    }
}
