<?php

namespace Webwizardsusa\OEmbed\Facades;

use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\HtmlString;
use Webwizardsusa\OEmbed\Embed;
use Webwizardsusa\OEmbed\Embedder;
use Webwizardsusa\OEmbed\OEmbedResponse;
use Webwizardsusa\OEmbed\Providers\Provider;
use Webwizardsusa\OEmbed\Providers\ProviderRegistry;
use Webwizardsusa\OEmbed\Rendering\EmbedMarkup;
use Webwizardsusa\OEmbed\Resolver;
use Webwizardsusa\OEmbed\Stores\EmbedStore;

/**
 * A new {@see Embedder} with the app's defaults each time, so `OEmbed::discovery()->render($url)` changes nothing
 * for the next call.
 *
 * @method static Embedder make()
 * @method static Embedder providers(array<Provider> $providers)
 * @method static Embedder withProviders(array<Provider> $providers)
 * @method static Embedder withoutProviders(array<string> $names)
 * @method static Embedder maxWidth(?int $width)
 * @method static Embedder privacyEnhancedYouTube(bool $condition = true)
 * @method static Embedder discovery(bool $condition = true)
 * @method static Embedder store(EmbedStore|string|null $store)
 * @method static Embedder extend(string $name, Closure(Container): EmbedStore $creator)
 * @method static EmbedStore getStore()
 * @method static bool isPrivacyEnhancedYouTube()
 * @method static bool discovers()
 * @method static int|null getMaxWidth()
 * @method static ProviderRegistry getProviders()
 * @method static Resolver getResolver()
 * @method static EmbedMarkup markup(string $classPrefix = 'oembed')
 * @method static Provider|null providerFor(string $url)
 * @method static bool takes(mixed $url)
 * @method static Embed|null resolve(string $url)
 * @method static array<string, Embed|null> resolveMany(iterable<mixed> $urls)
 * @method static HtmlString render(?string $url, ?string $caption = null)
 * @method static HtmlString renderAnswer(string $url, OEmbedResponse $answer, ?string $caption = null)
 * @method static string stylesheet()
 *
 * @see Embedder
 */
class OEmbed extends Facade
{
    protected static $cached = false;

    protected static function getFacadeAccessor(): string
    {
        return Embedder::class;
    }
}
