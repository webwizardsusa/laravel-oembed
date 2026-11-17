---
title: App-wide providers and defaults
weight: 20
description: Change the providers and settings every embedder starts with by binding your own Embedder in the container.
---

`config/oembed.php` sets the app's defaults for the store, `maxwidth`, discovery and privacy-enhanced YouTube, but
there is **no config key for providers**. To add a provider everywhere, or to remove one, give the container an
embedder that starts with your settings.

This works because every embedder is resolved from the container: the `OEmbed` facade, `Embedder::make()`, the
`<x-oembed>` component, the Filament field, entry and column, and other packages built on this one, such as
[Rich Editor Plus Embed](docs:rich-editor-plus-embed/introduction).

## Subclass the embedder

```php
namespace App\Support;

use Webwizardsusa\OEmbed\Embedder;
use Webwizardsusa\OEmbed\Providers\Provider;

class AppEmbedder extends Embedder
{
    public function __construct()
    {
        parent::__construct();

        $this->withoutProviders(['tiktok'])->withProviders([
            Provider::make('loom')
                ->label('Loom')
                ->urls(['*.loom.com/share/*'])
                ->endpoint('https://www.loom.com/v1/oembed')
                ->iframe(['www.loom.com']),
        ]);
    }
}
```

Call `parent::__construct()` first: it reads the config defaults. Then apply your settings with the same fluent
methods as anywhere else.

## Bind it

In a service provider's `register()` method:

```php
use App\Support\AppEmbedder;
use Webwizardsusa\OEmbed\Embedder;

public function register(): void
{
    $this->app->bind(Embedder::class, AppEmbedder::class);
}
```

Every embedder now starts with Loom and without TikTok, and each can still change its own copy:

```php
OEmbed::render($loomUrl);                        // uses Loom
OEmbed::withoutProviders(['loom'])->render($url); // this call only
```

> [!WARNING]
> Use `bind()`, not `singleton()`. Each embedder must be a new object: the fluent methods change the embedder they are
> called on, so a shared one would carry one call's settings into every later call.

## A closure instead of a class

For a few settings, a closure binding does the same without a class:

```php
use Webwizardsusa\OEmbed\Embedder;

$this->app->bind(Embedder::class, fn (): Embedder => (new Embedder)
    ->withoutProviders(['tiktok'])
    ->maxWidth(1200));
```

## Overriding behaviour

Because the bound class is used everywhere, a subclass can also change behaviour, such as wrapping
`render()` to add your own markup around every embed. Keep the parent's return types, and call the parent methods
rather than rebuilding markup: they are what keeps rendering safe (see
[How embeds are rendered](../security/rendering.md)).

```php
use Illuminate\Support\HtmlString;

public function render(?string $url, ?string $caption = null): HtmlString
{
    $html = parent::render($url, $caption);

    return $html->isEmpty() ? $html : new HtmlString('<div class="embed-wrapper">'.$html.'</div>');
}
```

`render()` is what the facade, the `<x-oembed>` component and the Filament entry print, and it covers links too. It
calls `renderAnswer()` for URLs that resolved, and `Embed::toHtml()` calls `renderAnswer()` directly, so override
`renderAnswer()` instead to change resolved embeds wherever they are printed.

Packages that build their own markup from the embedder's answers, such as Rich Editor Plus Embed, pick up your
providers and settings but not overridden rendering methods.
