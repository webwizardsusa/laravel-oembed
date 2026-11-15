<?php

return [
    /*
     * The cache store oEmbed answers are kept in (null for the default store), how long an answer is kept, and how
     * long a failed lookup is remembered before it is tried again.
     */
    'cache' => [
        'store' => env('OEMBED_CACHE_STORE'),
        'ttl' => 60 * 60 * 24 * 7,
        'failure_ttl' => 60 * 10,
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
