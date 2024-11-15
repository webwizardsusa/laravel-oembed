<?php

namespace Webwizardsusa\OEmbed\Providers;

use Webwizardsusa\OEmbed\Exceptions\InvalidOembedResponse;
use Webwizardsusa\OEmbed\OEmbedResponse;
use Webwizardsusa\OEmbed\OEmbedUrl;

class ScribdProvider extends AbstractOembedProvider
{

    protected bool $renderHtml = true;
    protected array $regexes = [
        '#https?://(www\.)?scribd\.com/(doc|document)/.*#i'
    ];

    protected string $oembedUrl = 'https://www.scribd.com/services/oembed';

    public function name(): string
    {
        return 'scribd';
    }


}
