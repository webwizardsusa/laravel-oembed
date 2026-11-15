<?php

namespace Webwizardsusa\OEmbed\Facades;

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\HtmlString;
use Webwizardsusa\OEmbed\Embed;
use Webwizardsusa\OEmbed\Embedder;

/**
 * A new {@see Embedder} with the app's defaults each time, so `OEmbed::discovery()->render($url)` changes nothing
 * for the next call.
 *
 * @method static Embedder discovery(bool $condition = true)
 * @method static Embedder privacyEnhancedYouTube(bool $condition = true)
 * @method static Embedder providers(array $providers)
 * @method static Embedder withProviders(array $providers)
 * @method static Embedder withoutProviders(array $names)
 * @method static Embedder maxWidth(?int $width)
 * @method static bool takes(mixed $url)
 * @method static Embed|null resolve(string $url)
 * @method static array<string, Embed|null> resolveMany(iterable $urls)
 * @method static HtmlString render(?string $url, ?string $caption = null)
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
