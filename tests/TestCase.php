<?php

namespace Webwizardsusa\OEmbed\Test;

use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase as Orchestra;
use Webwizardsusa\OEmbed\OEmbedServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [OEmbedServiceProvider::class];
    }

    public function fakeResponses(): void
    {
        $map = [
            '*youtube.com/*' => __DIR__ . '/Support/Responses/youtube.json',
            '*twitter.com/*' => __DIR__ . '/Support/Responses/twitter.json',
            '*bsky.app/oembed*' => __DIR__ . '/Support/Responses/bluesky.json',
            '*vimeo.com/*' => __DIR__ . '/Support/Responses/vimeo.json',
            '*dailymotion.com*' => __DIR__ . '/Support/Responses/dailymotion.json',
            '*soundcloud.com*' => __DIR__ . '/Support/Responses/soundcloud.json',
            '*scribd.com*' => __DIR__ . '/Support/Responses/scribd.json',
            '*spotify.com*' => __DIR__ . '/Support/Responses/spotify.json',
            '*tumblr.com*' => __DIR__ . '/Support/Responses/tumblr.json',
            '*tiktok.com*' => __DIR__ . '/Support/Responses/tiktok.json',
            '*pinterest.com/*' => __DIR__ . '/Support/Responses/pinterest.json',
            '*amazon.com/*' => __DIR__ . '/Support/Responses/amazon.json',
        ];

        $fakeResponses = collect($map)
            ->mapWithKeys(function ($file, $url) {
                return [$url => Http::response(file_get_contents($file), 200)];
            })
            ->toArray();
        Http::fake($fakeResponses);
    }

    public function urlMap(): array
    {
        return [
            'twitter' => [
                'https://twitter.com/PovilasKorop/status/1856727547515392151'
            ],
            'youtube' => [
                'https://www.youtube.com/watch?v=qGCYYkpENwQ'
            ],
            'flickr' => [
                'https://www.flickr.com/photos/laraveltw/44191083634/'
            ],
            'dailymotion' => [
                'https://www.dailymotion.com/video/x997s7e'
            ],
            'vimeo' => [
                'https://vimeo.com/channels/bestofstaffpicks/1019210228'
            ],
            'bluesky' => [
                'https://bsky.app/profile/intoxinat1ion.bsky.social/post/3laynh5r3uk2y'
            ],
            'soundcloud' => [
                'https://soundcloud.com/bloomukmusic/want-u-bloom'
            ],
            'scribd' => [
                'https://www.scribd.com/doc/47860543/Quotes'
            ],
            'spotify' => [
                'https://open.spotify.com/track/1CsMKhwEmNnmvHUuO5nryA?si=890b78a5c79641b3&nd=1&dlsi=31497848a6d3461e'
            ],
            'tumblr' => [
                'https://www.tumblr.com/fucxingcuties/765979998342545408?source=share'
            ],
            'amazon' => [
                'https://www.amazon.com/Brother-MFC-J1800DW-Automatic-Subscription-Replenishment/dp/B0CGCS8RWJ'
            ],
            'tiktok' => [
                'https://www.tiktok.com/@morefilterdude/video/7434209715447680302'
            ],
            'pinterest' => [
                'https://www.pinterest.com/pin/146930006579727073/'
            ],

        ];
    }

    public function getUrl(string $provider): string
    {
        return $this->urlMap()[$provider][0];
    }
}
