<?php

namespace Webwizardsusa\OEmbed;

use Webwizardsusa\OEmbed\Providers\Provider;
use Webwizardsusa\OEmbed\Providers\ProviderRegistry;

/**
 * Looks URLs up through their providers: answers come from the cache when they can, and the rest are fetched together
 * in one pool and cached.
 *
 * With {@see Discovery}, URLs no provider takes are looked up through the oEmbed endpoint their page names. Pages are
 * fetched one at a time, so at most {@see MAX_DISCOVERIES} uncached ones are discovered per call; the rest answer
 * null and are tried on a later call.
 */
class Resolver
{
    public const int MAX_DISCOVERIES = 5;

    public function __construct(
        protected ProviderRegistry $providers,
        protected OEmbedClient $client,
        protected EmbedCache $cache,
        protected ?int $maxWidth = null,
        protected ?Discovery $discovery = null,
    ) {}

    public function resolve(string $url): ?OEmbedResponse
    {
        return $this->resolveMany([$url])[$url] ?? null;
    }

    /**
     * @param  array<string>  $urls
     * @return array<string, ?OEmbedResponse> By URL. Null for a URL nothing takes, or whose lookup failed.
     */
    public function resolveMany(array $urls): array
    {
        $answers = [];
        $requests = [];
        $discoverable = [];

        foreach (array_unique($urls) as $url) {
            $answers[$url] = null;
            $provider = $this->providers->match($url);

            if ($provider !== null) {
                $requests[EmbedCache::key($provider->getName(), $url, $this->maxWidth)] = ['provider' => $provider, 'url' => $url];
            } elseif ($this->discovery !== null) {
                $discoverable[EmbedCache::key('discovered', $url, $this->maxWidth)] = $url;
            }
        }

        return [...$answers, ...$this->resolveFromProviders($requests), ...$this->resolveByDiscovery($discoverable)];
    }

    /**
     * @param  array<string, array{provider: Provider, url: string}>  $requests
     * @return array<string, ?OEmbedResponse> By URL.
     */
    protected function resolveFromProviders(array $requests): array
    {
        $answers = [];
        $cached = $this->cache->getMany(array_keys($requests));
        $missing = array_diff_key($requests, $cached);

        foreach ($this->client->fetchMany($missing, $this->maxWidth) as $key => $response) {
            $this->cache->put($key, $response);
            $cached[$key] = $response ?? false;
        }

        foreach ($requests as $key => $request) {
            $answers[$request['url']] = ($cached[$key] ?? false) ?: null;
        }

        return $answers;
    }

    /**
     * @param  array<string, string>  $urls  URLs by cache key.
     * @return array<string, ?OEmbedResponse> By URL.
     */
    protected function resolveByDiscovery(array $urls): array
    {
        if ($urls === [] || $this->discovery === null) {
            return [];
        }

        $cached = $this->cache->getMany(array_keys($urls));
        $answers = [];

        foreach (array_slice(array_diff_key($urls, $cached), 0, static::MAX_DISCOVERIES, true) as $key => $url) {
            $response = $this->discovery->discover($url, $this->maxWidth);
            $this->cache->put($key, $response);
            $cached[$key] = $response ?? false;
        }

        foreach ($urls as $key => $url) {
            $answers[$url] = ($cached[$key] ?? false) ?: null;
        }

        return $answers;
    }
}
