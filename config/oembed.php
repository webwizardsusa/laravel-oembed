<?php
return [
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
        'enabled' => env('OEMBED_ENABLE_CACHE', true), // Allow enabling/disabling caching
        'ttl' => env('OEMBED_URL_CACHE_TTL', 60 * 24 * 7), // Default time-to-live in minutes
        'invalid_ttl' => env('OEMBED_INVALID_URL_CACHE_TTL', 10), // Default time-to-live in minutes
        'key_prefix' => env('OEMBED_CACHE_PREFIX', 'oembed_url_'), // Prefix for cache keys
        'driver' => env('OEMBED_CACHE_DRIVER', null), // Optional cache driver
    ],

];
