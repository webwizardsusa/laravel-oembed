<?php

return [
    /*
     * Where oEmbed answers are kept between lookups: `cache` (a Laravel cache store), `database` (a table: publish
     * the migration with `php artisan vendor:publish --tag=oembed-migrations` and migrate first), or a store added
     * with `OEmbed::extend()`.
     */
    'store' => env('OEMBED_STORE', 'cache'),

    /*
     * How long an answer is kept, and how long a failed lookup is remembered before it is tried again, in every store.
     */
    'ttl' => 60 * 60 * 24 * 7,

    'failure_ttl' => 60 * 10,

    /*
     * The cache store for the `cache` store (null for the default one).
     */
    'cache' => [
        'store' => env('OEMBED_CACHE_STORE'),
    ],

    /*
     * The connection (null for the default one) and table for the `database` store.
     */
    'database' => [
        'connection' => env('OEMBED_DB_CONNECTION'),
        'table' => 'oembed_responses',
    ],

    /*
     * Seconds to wait for a provider's oEmbed endpoint, or for a page found by discovery.
     */
    'timeout' => 3,

    /*
     * The `maxwidth` sent to providers.
     */
    'max_width' => 800,

    /*
     * Defaults for every Embedder (each can change them): oEmbed discovery for sites that aren't providers, and
     * playing YouTube from youtube-nocookie.com.
     */
    'discovery' => false,

    'privacy_enhanced_youtube' => false,
];
