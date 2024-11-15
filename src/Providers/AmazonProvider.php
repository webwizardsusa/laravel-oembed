<?php

namespace Webwizardsusa\OEmbed\Providers;

use Webwizardsusa\OEmbed\Exceptions\InvalidOembedResponse;
use Webwizardsusa\OEmbed\OEmbedResponse;
use Webwizardsusa\OEmbed\OEmbedUrl;

class AmazonProvider extends AbstractOembedProvider
{


    protected bool $responsiveFrame = false;

    public function name(): string
    {
        return 'amazon';
    }

    protected function regexMap(): array {
        return [
            '#https?://([a-z0-9-]+\.)?amazon\.(com|com\.mx|com\.br|ca)/.*#i' =>  'https://read.amazon.com/kp/api/oembed',
            '#https?://([a-z0-9-]+\.)?amazon\.(co\.uk|de|fr|it|es|in|nl|ru)/.*#i' =>  'https://read.amazon.co.uk/kp/api/oembed',
            '#https?://([a-z0-9-]+\.)?amazon\.(co\.jp|com\.au)/.*#i' =>  'https://read.amazon.com.au/kp/api/oembed',
            '#https?://([a-z0-9-]+\.)?amazon\.cn/.*#i'     =>  'https://read.amazon.cn/kp/api/oembed',
            '#https?://(www\.)?a\.co/.*#i'                 =>  'https://read.amazon.com/kp/api/oembed',
            '#https?://(www\.)?amzn\.to/.*#i'              =>  'https://read.amazon.com/kp/api/oembed',
            '#https?://(www\.)?amzn\.eu/.*#i'              =>  'https://read.amazon.co.uk/kp/api/oembed',
            '#https?://(www\.)?amzn\.in/.*#i'              =>  'https://read.amazon.in/kp/api/oembed',
            '#https?://(www\.)?amzn\.asia/.*#i'            =>  'https://read.amazon.com.au/kp/api/oembed',
        ];

    }
    public function extract(OEmbedUrl $url): OEmbedResponse|false
    {
        foreach($this->regexMap() as $pattern=>$oembedUrl) {
            if (preg_match($pattern, $url->url())) {
                return $this->pull($url, $oembedUrl);
            }
        }

        return false;
    }



}
