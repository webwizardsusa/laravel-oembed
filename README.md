# OEmbed For Laravel

**(NOTE: This package is in early development.)**

This package allows you to retrieve embed information for URLs from various oembed providers, similar to Wordpress. Out
of the box, we support the following websites:

- Amazon
- Bluesky
- Dailymotion
- Flickr
- Pinterest
- Scribd
- Sound Cloud
- Spotify
- Tiktok
- Tumbler
- Twitter
- Vimeo
- Youtube

Providers can be disabled inside the configuration. Adding of additional providers is a rather simple process. See
below.

## Installation

You can install the package via composer:

```
composer require webwizardsusa/laravel-oembed
```

#### Responsive Iframe CSS

This package makes responsive IFrames possible. To enabled them on your site, you need to add the following CSS to your
site:

```css
.oembed-responsive-container {
    position: relative;
    width: 100%;
    height: 0;
    overflow: hidden;
}

.oembed-responsive-container iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}
```

#### Configuration publishing

If you wish to alter the list of providers, or adjust any other configuration values, then you can publish the
configuration with:

```bash
php artisan vendor:publish --tag=oembed-config
```

The following configuration values are available:

| Config            | Env                          | Default     | Description                                         |
|-------------------|------------------------------|-------------|-----------------------------------------------------|
| providers         | -                            | array       | An array of enabled provider classes                |
| cache.enabled     | OEMBED_ENABLE_CACHE          | true        | Enables caching of responses                        |
| cache.ttl         | OEMBED_URL_CACHE_TTL         | 10080       | The number of minutes to cache successful responses |
| cache.invalid_ttl | OEMBED_INVALID_URL_CACHE_TTL | 10          | The number of minutes to cache invalid responses    |
| cachek.key_prefix | OEMBED_CACHE_PREFIX          | oembed_url_ | A prefix to attach to the key of all cached items   |
| cache.driver      | OEMBED_CACHE_DRIVER          | null        | The cache driver to use                             |

## Usage

Usage is very simple. Just utilize the OEmbed service to retrieve information about a URL.

```php
app(\Webwizardsusa\OEmbed\OEmbed::class)->fromUrl('https://www.youtube.com/watch?v=gaeV9Jp3Uhk')->toArray()

```

Running that will result in the following output:

```json
{
  "url": "https://www.youtube.com/watch?v=gaeV9Jp3Uhk",
  "provider": "youtube",
  "embed_code": "<iframe width=\"200\" height=\"150\" src=\"https://www.youtube.com/embed/gaeV9Jp3Uhk?feature=oembed\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share\" referrerpolicy=\"strict-origin-when-cross-origin\" allowfullscreen title=\"Filament Plugin: Relation Manager as Table Modal\"></iframe>",
  "raw": {
    "title": "Filament Plugin: Relation Manager as Table Modal",
    "author_name": "Filament Daily",
    "author_url": "https://www.youtube.com/@FilamentDaily",
    "type": "video",
    "height": 150,
    "width": 200,
    "version": "1.0",
    "provider_name": "YouTube",
    "provider_url": "https://www.youtube.com/",
    "thumbnail_height": 360,
    "thumbnail_width": 480,
    "thumbnail_url": "https://i.ytimg.com/vi/gaeV9Jp3Uhk/hqdefault.jpg",
    "html": "<iframe width=\"200\" height=\"150\" src=\"https://www.youtube.com/embed/gaeV9Jp3Uhk?feature=oembed\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share\" referrerpolicy=\"strict-origin-when-cross-origin\" allowfullscreen title=\"Filament Plugin: Relation Manager as Table Modal\"></iframe>"
  },
  "width": 200,
  "height": 150,
  "retrieved_at": "2024-11-20T11:30:34.528865Z"
}

```

The fromUrl() method will return a OEmbedResponse if successful, or null if not successful.

You can also render the embed from the OEmbedResponse by calling the render() method. Rending is done through views. Each provider decides which view to use, html, iframe or responsive-iframe. 

## Providers

For the most part providers are very simple classes:

```php
class YoutubeProvider extends AbstractOembedProvider
{

    protected array $regexes = [
        '#https?://((m|www)\.)?youtube\.com/watch.*#i',
        '#https?://((m|www)\.)?youtube\.com/playlist.*#i',
        '#https?://((m|www)\.)?youtube\.com/shorts/*#i',
        '#https?://((m|www)\.)?youtube\.com/live/*#i',
        '#https?://youtu\.be/.*#i',
    ];
    protected string $oembedUrl = 'https://www.youtube.com/oembed';


    public function name(): string
    {
        return 'youtube';
    }
}

```

A simple array of regex patterns to match the URL, then the url of that services oembed service. While this works for a majority of the providers, sometimes you may need to extend how information is retrieved. To do that, you can override pull() on the provider class:

```php
   public function pull(OEmbedUrl $url, $apiUrl):OEmbedResponse
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

This package is developed by [WebWizardsUSA](https://webwizardsusa.com/).

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
