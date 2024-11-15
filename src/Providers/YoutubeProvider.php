<?php

namespace Webwizardsusa\OEmbed\Providers;

use Webwizardsusa\OEmbed\Exceptions\InvalidOembedResponse;
use Webwizardsusa\OEmbed\OEmbedResponse;
use Webwizardsusa\OEmbed\OEmbedUrl;

class YoutubeProvider extends AbstractOembedProvider
{

    protected array $regexes = [
        '#https?://((m|www)\.)?youtube\.com/watch.*#i',
        '#https?://((m|www)\.)?youtube\.com/playlist.*#i',
        '#https?://((m|www)\.)?youtube\.com/shorts/*#i',
        '#https?://((m|www)\.)?youtube\.com/live/*#i',
        '#https?://youtu\.be/.*#i',
    ];
    protected string $oembedUrl = 'https://www.youtube.com/oembed';


    public function name(): string
    {
        return 'youtube';
    }
}
