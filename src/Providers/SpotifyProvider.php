<?php

namespace Webwizardsusa\OEmbed\Providers;

use Webwizardsusa\OEmbed\Exceptions\InvalidOembedResponse;
use Webwizardsusa\OEmbed\OEmbedResponse;
use Webwizardsusa\OEmbed\OEmbedUrl;

class SpotifyProvider extends AbstractOembedProvider
{

    protected array $regexes = [
        '#https?://(open|play)\.spotify\.com/.*#i'
    ];

    protected string $oembedUrl = 'https://embed.spotify.com/oembed';

    public function name(): string
    {
        return 'spotify';
    }


}
