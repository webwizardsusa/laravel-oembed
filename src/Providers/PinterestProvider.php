<?php

namespace Webwizardsusa\OEmbed\Providers;

use Webwizardsusa\OEmbed\Exceptions\InvalidOembedResponse;
use Webwizardsusa\OEmbed\OEmbedResponse;
use Webwizardsusa\OEmbed\OEmbedUrl;

class PinterestProvider extends AbstractOembedProvider
{

    protected bool $renderHtml = true;
    protected array $regexes = [
        '#https?://([a-z]{2}|www)\.pinterest\.com(\.(au|mx))?/.*#i'
    ];

    protected string $oembedUrl = 'https://www.pinterest.com/oembed.json';

    public function name(): string
    {
        return 'pinterest';
    }
}
