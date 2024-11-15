<?php

namespace Webwizardsusa\OEmbed\Providers;

use Webwizardsusa\OEmbed\Exceptions\InvalidOembedResponse;
use Webwizardsusa\OEmbed\OEmbedResponse;
use Webwizardsusa\OEmbed\OEmbedUrl;

class DailymotionProvider extends AbstractOembedProvider
{

    protected array $regexes = [
        '#https?://(www\.)?dailymotion\.com/.*#i',
        '#https?://dai\.ly/.*#i'
    ];

    protected string $oembedUrl = 'https://www.dailymotion.com/services/oembed';

    public function name(): string
    {
        return 'dailymotion';
    }

}
