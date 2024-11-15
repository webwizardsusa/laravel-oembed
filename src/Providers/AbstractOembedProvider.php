<?php

namespace Webwizardsusa\OEmbed\Providers;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Webwizardsusa\OEmbed\Exceptions\InvalidOembedResponse;
use Webwizardsusa\OEmbed\OEmbedResponse;
use Webwizardsusa\OEmbed\OEmbedUrl;

abstract class AbstractOembedProvider
{

    protected array $regexes = [];

    protected string $oembedUrl = '';

    protected bool $renderHtml = false;

    protected bool $responsiveFrame = true;
    abstract public function name(): string;



    public function render(OEmbedResponse $response): mixed {
        if ($this->renderHtml) {
            return $this->renderHtml($response);
        }

        return $this->renderIframe($response, $this->responsiveFrame);
    }

    public function renderHtml(OEmbedResponse $response): mixed
    {
        return view('laravel-oembed::html', ['response' => $response]);
    }

    public function renderIframe(OEmbedResponse $response, bool $responsive = true): mixed
    {
        $view = $responsive && $response->getWidth() && $response->getHeight() ? 'laravel-oembed::responsive-iframe' : 'laravel-oembed::iframe';
        return view($view, ['response' => $response]);
    }


    protected function findFromRegex(OEmbedUrl|string $url): bool
    {
        foreach($this->regexes as $regex) {
            if (preg_match($regex, $url)) {
                return true;
            }
        }
        return false;
    }

    public function pull(OEmbedUrl $url, $apiUrl):OEmbedResponse {

        $response = $this->retrieve($apiUrl, ['url' => $url->url()]);
        if ($response->json()) {
            return OEmbedResponse::make($url->url(), $this->name(), $response->json());
        }

        throw new InvalidOembedResponse('Invalid ' . $this->name() . ' oembed response', $response->getStatusCode());
    }
    public function extract(OEmbedUrl $url): OEmbedResponse|false
    {
        if (!$this->findFromRegex($url)) {
            return false;
        }
        if (!$this->oembedUrl) {
            throw new \RuntimeException('OEmbed provider must define a $oembedUrl property');
        }
        return $this->pull($url, $this->oembedUrl);

    }



    public function retrieve(string $url, array|null|string $query = null, array $headers = []): PromiseInterface|Response
    {
        $headers = array_merge([
            'User-Agent' => 'WordPress/6.3.1; http://example.com',
            'Accept' => 'application/json, text/html',
            'Referer' => 'http://example.com/sample-post',
            'Connection' => 'keep-alive',
        ], $headers);

        return Http::withHeaders($headers)
            ->get($url, $query);
    }

    public function makeResponse(OEmbedUrl $url, array $data): OEmbedResponse
    {
        return OEmbedResponse::make($url->url(), $this->name(), $data);
    }

    public function getRegexes(): array
    {
        return $this->regexes;
    }

    public function setRegexes(array $regexes): AbstractOembedProvider
    {
        $this->regexes = $regexes;
        return $this;
    }

    public function getOembedUrl(): string
    {
        return $this->oembedUrl;
    }

    public function setOembedUrl(string $oembedUrl): AbstractOembedProvider
    {
        $this->oembedUrl = $oembedUrl;
        return $this;
    }


}
