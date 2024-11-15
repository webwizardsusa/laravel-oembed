<?php

namespace Webwizardsusa\OEmbed\Providers;

use Illuminate\Support\Facades\Http;
use Webwizardsusa\OEmbed\Exceptions\InvalidOembedResponse;
use Webwizardsusa\OEmbed\Exceptions\OembedNotFoundException;
use Webwizardsusa\OEmbed\OEmbedResponse;
use Webwizardsusa\OEmbed\OEmbedUrl;

class TwitterProvider extends AbstractOembedProvider
{

    public function extract(OEmbedUrl $url): OEmbedResponse|false
    {
        if (!$url->domainOf('x.com') && !$url->domainOf('twitter.com')) {
            return false;
        }

        // Twitter is buggy on OEmbed, so we grab it like this.
        $response = Http::get('https://publish.twitter.com/oembed?url=' . urlencode($url->url()));

        $json = $response->json();
        if ($json) {
            return $this->makeResponse($url, $json);
        }
        if ($response->status() === 404) {
            throw new OembedNotFoundException();
        }
        throw new InvalidOembedResponse($response->status(), $response->getStatusCode());

    }

    public function name(): string
    {
        return 'twitter';
    }

    public function render(OEmbedResponse $response): mixed
    {
        return $this->renderHtml($response);
    }
}
