<?php

namespace Webwizardsusa\OEmbed\Providers;

use Webwizardsusa\OEmbed\Exceptions\InvalidOembedResponse;
use Webwizardsusa\OEmbed\OEmbedResponse;
use Webwizardsusa\OEmbed\OEmbedUrl;

class TumblrProvider extends AbstractOembedProvider
{

    protected bool $renderHtml = true;
    protected array $regexes = [
        '#https?://(.+)\.tumblr\.com/.*#i'
    ];

    protected string $oembedUrl = 'https://www.tumblr.com/oembed/1.0';

    public function name(): string
    {
        return 'tumblr';
    }


}
