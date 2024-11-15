<?php

namespace Webwizardsusa\OEmbed;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Webwizardsusa\OEmbed\Exceptions\OEmbedException;
use Webwizardsusa\OEmbed\Providers\AbstractOembedProvider;

class OEmbed
{

    /** @var AbstractOembedProvider[] | Collection */
    protected Collection|array $providers;

    public function __construct(array $providers = [])
    {
        $this->providers = collect();
        $this->register(...$providers);
    }

    public function register(...$providers): static
    {
        foreach ($providers as $provider) {
            if (is_array($provider)) {
                return $this->register(...$provider);
            }

            $instance = new $provider();
            $this->providers->put($instance->name(), $instance);
        }

        return $this;
    }

    public function get(string $providerName): ?AbstractOembedProvider
    {
        return $this->providers->get($providerName);
    }

    public function has(string $providerName): bool
    {
        return $this->providers->has($providerName);
    }

    /**
     * @return AbstractOembedProvider[] | Collection
     */
    public function all(): Collection|array
    {
        return $this->providers;
    }


    public function fromUrl(string|OEmbedUrl $url, bool $useCache = true, bool $throw = false): ?OEmbedResponse
    {
        return app(UrlParser::class)->retrieve($url, $useCache, $throw);
    }
}
