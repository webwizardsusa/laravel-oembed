<?php
return [
    /*
     * An array of the provider classes to enable
     */
    'providers' => [
        \Webwizardsusa\OEmbed\Providers\YoutubeProvider::class,
        \Webwizardsusa\OEmbed\Providers\TwitterProvider::class,
        \Webwizardsusa\OEmbed\Providers\BlueskyProvider::class,
        \Webwizardsusa\OEmbed\Providers\FilckrProvider::class,
        \Webwizardsusa\OEmbed\Providers\SoundcloudProvider::class,
        \Webwizardsusa\OEmbed\Providers\SpotifyProvider::class,
        \Webwizardsusa\OEmbed\Providers\PinterestProvider::class,
        \Webwizardsusa\OEmbed\Providers\TiktokProvider::class,
        \Webwizardsusa\OEmbed\Providers\DailymotionProvider::class,
        \Webwizardsusa\OEmbed\Providers\ScribdProvider::class,
        \Webwizardsusa\OEmbed\Providers\VimeoProvider::class,
        \Webwizardsusa\OEmbed\Providers\AmazonProvider::class,
        \Webwizardsusa\OEmbed\Providers\TumblrProvider::class,
    ],

    'cache' => [
        /*
         * Enable or disable caching.
         */
        'enabled' => env('OEMBED_ENABLE_CACHE', true),

        /*
         * The number of minutes to store valid responses.
         */
        'ttl' => env('OEMBED_URL_CACHE_TTL', 60 * 24 * 7),

        /*
         * The number of minutes to cache invalid responses.
         */
        'invalid_ttl' => env('OEMBED_INVALID_URL_CACHE_TTL', 10),

        /*
         * The prefix to attach to the key of cached items.
         */
        'key_prefix' => env('OEMBED_CACHE_PREFIX', 'oembed_url_'),

        /*
         * The driver to use to cache responses.
         */
        'driver' => env('OEMBED_CACHE_DRIVER', null),
    ],

];
