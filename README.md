# Laravel oEmbed

Turn YouTube, Vimeo, X and other URLs into embeds in Laravel, safely. The provider's HTML is never output as it
came: players are rebuilt from the provider's own hosts, posts from their text and links, and anything else shows as
a link. Answers are kept in swappable stores (the cache, a database table, or your own), optional oEmbed discovery
handles other sites behind a guard that keeps lookups off private networks, and with Filament you also get a form
field, an infolist entry and a table column.

Requires PHP 8.3+ and Laravel 12 or 13. Filament 5 is optional.

```bash
composer require webwizardsusa/laravel-oembed
```

```blade
<style>{!! OEmbed::stylesheet() !!}</style>

<x-oembed :url="$post->video_url" caption="Watch this" />
```

```php
use Webwizardsusa\OEmbed\Facades\OEmbed;
use Webwizardsusa\OEmbed\Filament\Forms\Components\OEmbedInput;

OEmbed::privacyEnhancedYouTube()->render('https://www.youtube.com/watch?v=dQw4w9WgXcQ');

OEmbedInput::make('video_url')->required();
```

## Documentation

Read the full documentation in the [`docs` folder](https://github.com/webwizardsusa/laravel-oembed/tree/main/docs).

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md).
