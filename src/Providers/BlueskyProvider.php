<?php

namespace Webwizardsusa\OEmbed\Providers;

use Webwizardsusa\OEmbed\Exceptions\InvalidOembedResponse;
use Webwizardsusa\OEmbed\OEmbedResponse;
use Webwizardsusa\OEmbed\OEmbedUrl;

class BlueskyProvider extends AbstractOembedProvider
{

    protected array $regexes = [
        '#https?://bsky.app/profile/.*/post/.*#i'
    ];
    protected string $oembedUrl = 'https://embed.bsky.app/oembed';

    public function name(): string
    {
        return 'bluesky';
    }
}
