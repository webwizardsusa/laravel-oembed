<?php

namespace Webwizardsusa\OEmbed\Providers;

/**
 * The providers an `Embedder` offers unless told otherwise. Each one needs no account or access token.
 */
class BuiltInProviders
{
    /**
     * @return array<Provider>
     */
    public static function all(): array
    {
        return [
            Provider::make('youtube')
                ->label('YouTube')
                ->urls(['*.youtube.com/watch*', '*.youtube.com/shorts/*', '*.youtube.com/live/*', '*.youtube.com/playlist*', 'youtu.be/*'])
                ->endpoint('https://www.youtube.com/oembed')
                ->iframe(['www.youtube.com', 'www.youtube-nocookie.com']),
            Provider::make('vimeo')
                ->label('Vimeo')
                ->urls(['vimeo.com/*', 'www.vimeo.com/*', 'player.vimeo.com/video/*'])
                ->endpoint('https://vimeo.com/api/oembed.json')
                ->iframe(['player.vimeo.com']),
            Provider::make('spotify')
                ->label('Spotify')
                ->urls(['open.spotify.com/*'])
                ->endpoint('https://open.spotify.com/oembed')
                ->iframe(['open.spotify.com']),
            Provider::make('soundcloud')
                ->label('SoundCloud')
                ->urls(['soundcloud.com/*', 'www.soundcloud.com/*', 'm.soundcloud.com/*', 'on.soundcloud.com/*'])
                ->endpoint('https://soundcloud.com/oembed')
                ->iframe(['w.soundcloud.com']),
            Provider::make('x')
                ->label('X')
                ->urls(['*.twitter.com/*/status/*', '*.x.com/*/status/*'])
                ->endpoint('https://publish.x.com/oembed')
                ->script('https://platform.x.com/widgets.js'),
            Provider::make('tiktok')
                ->label('TikTok')
                ->urls(['*.tiktok.com/@*/video/*'])
                ->endpoint('https://www.tiktok.com/oembed')
                ->iframe(['www.tiktok.com']),
            Provider::make('bluesky')
                ->label('Bluesky')
                ->urls(['bsky.app/profile/*/post/*'])
                ->endpoint('https://embed.bsky.app/oembed')
                ->script('https://embed.bsky.app/static/embed.js'),
            Provider::make('flickr')
                ->label('Flickr')
                ->urls(['*.flickr.com/photos/*', 'flic.kr/p/*'])
                ->endpoint('https://www.flickr.com/services/oembed/'),
        ];
    }
}
