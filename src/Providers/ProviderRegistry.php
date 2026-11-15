<?php

namespace Webwizardsusa\OEmbed\Providers;

/**
 * The providers a field embeds from, by name. A provider added under a name already present replaces it.
 */
class ProviderRegistry
{
    /**
     * URLs longer than this are never matched.
     */
    public const int MAX_URL_LENGTH = 2048;

    /**
     * @var array<string, Provider>
     */
    protected array $providers = [];

    /**
     * @param  array<Provider>  $providers
     */
    final public function __construct(array $providers = [])
    {
        $this->add($providers);
    }

    /**
     * @param  array<Provider>  $providers
     */
    public static function make(array $providers): static
    {
        return new static($providers);
    }

    /**
     * @param  array<Provider>  $providers
     */
    public function add(array $providers): static
    {
        foreach ($providers as $provider) {
            $this->providers[$provider->getName()] = $provider;
        }

        return $this;
    }

    /**
     * @param  array<string>  $names
     */
    public function remove(array $names): static
    {
        foreach ($names as $name) {
            unset($this->providers[$name]);
        }

        return $this;
    }

    /**
     * The first provider, in the order they were added, whose patterns match the URL.
     */
    public function match(mixed $url): ?Provider
    {
        if (! is_string($url) || strlen($url) > static::MAX_URL_LENGTH) {
            return null;
        }

        foreach ($this->providers as $provider) {
            if ($provider->matches($url)) {
                return $provider;
            }
        }

        return null;
    }

    public function get(string $name): ?Provider
    {
        return $this->providers[$name] ?? null;
    }

    /**
     * @return array<string, Provider>
     */
    public function all(): array
    {
        return $this->providers;
    }
}
