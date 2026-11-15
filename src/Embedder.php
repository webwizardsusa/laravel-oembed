<?php

namespace Webwizardsusa\OEmbed;

use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\HtmlString;
use Webwizardsusa\OEmbed\Providers\BuiltInProviders;
use Webwizardsusa\OEmbed\Providers\Provider;
use Webwizardsusa\OEmbed\Providers\ProviderRegistry;
use Webwizardsusa\OEmbed\Rendering\EmbedMarkup;
use Webwizardsusa\OEmbed\Rendering\HtmlBuilder;
use Webwizardsusa\OEmbed\Stores\EmbedStore;
use Webwizardsusa\OEmbed\Stores\EmbedStoreManager;
use Webwizardsusa\OEmbed\Support\Url;

/**
 * Which URLs can be embedded and how: the providers, discovery, the `maxwidth` sent to providers, privacy-enhanced
 * YouTube and where answers are stored. It looks URLs up ({@see resolve()}, {@see resolveMany()}) and renders them
 * ({@see render()}).
 *
 * A new one takes its defaults from `config/oembed.php`. The `OEmbed` facade gives a new one each time, so its fluent
 * methods never change another one's settings.
 */
class Embedder
{
    /**
     * @var array<Provider> | null
     */
    protected ?array $providers = null;

    /**
     * @var array<Provider>
     */
    protected array $addedProviders = [];

    /**
     * @var array<string>
     */
    protected array $removedProviders = [];

    protected ?int $maxWidth;

    protected bool $isPrivacyEnhancedYouTube;

    protected bool $discovers;

    /**
     * A store name from {@see EmbedStoreManager}, a store, or null for the default (`oembed.store`).
     */
    protected EmbedStore|string|null $store = null;

    public function __construct()
    {
        $maxWidth = config('oembed.max_width');

        $this->maxWidth = is_numeric($maxWidth) ? (int) $maxWidth : null;
        $this->isPrivacyEnhancedYouTube = (bool) config('oembed.privacy_enhanced_youtube', false);
        $this->discovers = (bool) config('oembed.discovery', false);
    }

    public static function make(): static
    {
        return app(static::class);
    }

    /**
     * Embeds from these providers instead of the built-in ones.
     *
     * @param  array<Provider>  $providers
     */
    public function providers(array $providers): static
    {
        $this->providers = $providers;

        return $this;
    }

    /**
     * Adds providers to the list; one with the name of a built-in provider replaces it.
     *
     * @param  array<Provider>  $providers
     */
    public function withProviders(array $providers): static
    {
        $this->addedProviders = [...$this->addedProviders, ...$providers];

        return $this;
    }

    /**
     * @param  array<string>  $names
     */
    public function withoutProviders(array $names): static
    {
        $this->removedProviders = [...$this->removedProviders, ...$names];

        return $this;
    }

    /**
     * The `maxwidth` sent to providers. Defaults to the `oembed.max_width` config value.
     */
    public function maxWidth(?int $width): static
    {
        $this->maxWidth = $width;

        return $this;
    }

    /**
     * Plays YouTube videos from `www.youtube-nocookie.com`, which sets no cookies until the video is played.
     */
    public function privacyEnhancedYouTube(bool $condition = true): static
    {
        $this->isPrivacyEnhancedYouTube = $condition;

        return $this;
    }

    /**
     * Also embeds URLs no provider takes, through the oEmbed endpoint their page names (oEmbed discovery). Off by
     * default: the server then fetches pages people link to. Every request is checked by {@see UrlGuard}, so private
     * and local addresses are never fetched. A discovered player may only come from the page's own host, and plays in
     * a sandbox; posts that need a script are shown as their link.
     */
    public function discovery(bool $condition = true): static
    {
        $this->discovers = $condition;

        return $this;
    }

    /**
     * Where answers are kept: a store name (`cache`, `database`, or one added with {@see extend()}) or a store.
     */
    public function store(EmbedStore|string|null $store): static
    {
        $this->store = $store;

        return $this;
    }

    /**
     * Adds a store that {@see store()} and `OEMBED_STORE` can name, for every embedder:
     * `OEmbed::extend('redis', fn ($app) => new RedisStore(…))`.
     *
     * @param  Closure(Container): EmbedStore  $creator
     */
    public function extend(string $name, Closure $creator): static
    {
        app(EmbedStoreManager::class)->extend($name, $creator);

        return $this;
    }

    public function getStore(): EmbedStore
    {
        return $this->store instanceof EmbedStore ? $this->store : app(EmbedStoreManager::class)->store($this->store);
    }

    public function isPrivacyEnhancedYouTube(): bool
    {
        return $this->isPrivacyEnhancedYouTube;
    }

    public function discovers(): bool
    {
        return $this->discovers;
    }

    public function getMaxWidth(): ?int
    {
        return $this->maxWidth;
    }

    public function getProviders(): ProviderRegistry
    {
        return ProviderRegistry::make($this->providers ?? BuiltInProviders::all())
            ->add($this->addedProviders)
            ->remove($this->removedProviders);
    }

    public function getResolver(): Resolver
    {
        return new Resolver(
            $this->getProviders(),
            OEmbedClient::fromConfig(),
            $this->getStore(),
            $this->maxWidth,
            $this->discovers ? Discovery::fromConfig() : null,
        );
    }

    /**
     * Builds providers' markup safely, for this embedder's settings.
     *
     * @param  string  $classPrefix  The player wrapper's class is `{$classPrefix}-frame`.
     */
    public function markup(string $classPrefix = 'oembed'): EmbedMarkup
    {
        return new EmbedMarkup($this->isPrivacyEnhancedYouTube, $classPrefix);
    }

    /**
     * The provider a URL is shown through: the one that takes it, or, with discovery, its page's own.
     */
    public function providerFor(string $url): ?Provider
    {
        return $this->getProviders()->match($url) ?? ($this->discovers ? Provider::discovered($url) : null);
    }

    /**
     * Whether the URL may be looked up at all: one a provider takes, or, with discovery, any http(s) URL.
     */
    public function takes(mixed $url): bool
    {
        if (! is_string($url) || ! Url::isAllowed($url) || strlen(trim($url)) > ProviderRegistry::MAX_URL_LENGTH) {
            return false;
        }

        return $this->discovers || $this->getProviders()->match(trim($url)) !== null;
    }

    /**
     * The embed for a URL, or null when nothing takes it or there is nothing to embed for it.
     */
    public function resolve(string $url): ?Embed
    {
        return $this->resolveMany([$url])[trim($url)] ?? null;
    }

    /**
     * Looks every URL up at once: cached answers first, the rest in one pooled request.
     *
     * @param  iterable<mixed>  $urls
     * @return array<string, ?Embed> By trimmed URL.
     */
    public function resolveMany(iterable $urls): array
    {
        $taken = [];

        foreach ($urls as $url) {
            if ($this->takes($url)) {
                $taken[trim($url)] = true;
            }
        }

        if ($taken === []) {
            return [];
        }

        $embeds = [];

        foreach ($this->getResolver()->resolveMany(array_keys($taken)) as $url => $answer) {
            $embeds[$url] = $answer === null ? null : new Embed($url, $this->getProviders()->match($url), $answer, $this);
        }

        return $embeds;
    }

    /**
     * The embed for a URL as HTML: `<figure class="oembed">` holding the provider's player, post or photo, rebuilt
     * safely, or its link when there is nothing safe to show, and the caption. Empty for a URL that isn't http(s).
     */
    public function render(?string $url, ?string $caption = null): HtmlString
    {
        if (! Url::isAllowed($url)) {
            return new HtmlString('');
        }

        $url = trim($url);
        $embed = $this->resolve($url);

        return $embed === null ? $this->renderLink($url, null, $caption) : $this->renderAnswer($url, $embed->response, $caption);
    }

    /**
     * An answer already looked up, rendered as {@see render()} does.
     */
    public function renderAnswer(string $url, OEmbedResponse $answer, ?string $caption = null): HtmlString
    {
        $provider = $this->providerFor($url);
        $display = $provider === null ? null : $this->markup()->build($provider, $answer, $url);

        if ($display === null) {
            return $this->renderLink($url, $answer->title, $caption);
        }

        return new HtmlString(HtmlBuilder::build([
            'figure',
            ['class' => 'oembed', 'data-kind' => $display['kind']],
            ...$display['content'],
            ...static::captionElement($caption),
        ]));
    }

    /**
     * The CSS rendered embeds need: responsive players, photos and captions. Include it on pages that show embeds.
     */
    public function stylesheet(): string
    {
        return (string) file_get_contents(dirname(__DIR__).'/resources/css/oembed.css');
    }

    protected function renderLink(string $url, ?string $title, ?string $caption): HtmlString
    {
        return new HtmlString(HtmlBuilder::build([
            'figure',
            ['class' => 'oembed'],
            ['a', ['href' => $url], filled($title) ? $title : $url],
            ...static::captionElement($caption),
        ]));
    }

    /**
     * @return array<array<mixed>>
     */
    protected static function captionElement(?string $caption): array
    {
        $caption = trim((string) $caption);

        return $caption === '' ? [] : [['figcaption', $caption]];
    }
}
