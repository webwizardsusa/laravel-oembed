# Laravel oEmbed

Turn YouTube, Vimeo, X and other URLs into embeds in Laravel, safely. The provider's HTML is never output as it
came: players are rebuilt from the provider's own hosts, posts from their text and links, and anything else shows as
a link. Optional oEmbed discovery handles other sites, behind a guard that keeps lookups off private networks.

Filament is optional. With it, you also get a form field, an infolist entry and a table column.

```bash
composer require webwizardsusa/laravel-oembed
```

```blade
<style>{!! OEmbed::stylesheet() !!}</style>

<x-oembed :url="$post->video_url" caption="Watch this" />
```

## Rendering

```php
use Webwizardsusa\OEmbed\Facades\OEmbed;

OEmbed::render('https://www.youtube.com/watch?v=dQw4w9WgXcQ', caption: 'Watch this');
```

This gives a `<figure class="oembed">` with the provider's markup, rebuilt:

- **Players** (YouTube, Vimeo, Spotify, SoundCloud, TikTok): the `<iframe>`, only if its `src` is https on one of the
  provider's own hosts, with a fixed set of attributes. Videos keep their aspect ratio as they scale.
- **Posts** (X, Bluesky): the provider's `<blockquote>`, kept to text, links and a few inline tags, followed by the
  provider's own script. A script in the answer is never used.
- **Photos** (Flickr): an `<img>` linking to the photo's page.
- **Anything else**, or a lookup that fails, renders as the link. A URL that isn't http(s) renders nothing.

Include `OEmbed::stylesheet()` on pages that show embeds. With Filament, it is registered in your panels.

### Looking URLs up

```php
$embed = OEmbed::resolve($url);        // ?Embed
$embed?->title();                      // "Rick Astley - Never Gonna Give You Up"
$embed?->thumbnail();                  // "https://i.ytimg.com/…"
$embed?->providerLabel();              // "YouTube"
$embed?->toHtml();                     // the same as OEmbed::render($url)

OEmbed::resolveMany($urls);            // [url => ?Embed], looked up at once
OEmbed::takes($url);                   // whether a provider (or discovery) takes the URL
```

Answers are stored, failed lookups included (see [Storage](#storage)). `resolveMany()` looks every URL it hasn't
stored up in one pooled request.

## Settings

`OEmbed` gives you a new `Embedder` with your app's defaults each time, so its methods only change that one:

```php
use Webwizardsusa\OEmbed\Providers\Provider;

OEmbed::withoutProviders(['tiktok'])
    ->withProviders([
        Provider::make('loom')
            ->label('Loom')
            ->urls(['*.loom.com/share/*'])
            ->endpoint('https://www.loom.com/v1/oembed')
            ->iframe(['www.loom.com']),
    ])
    ->privacyEnhancedYouTube()
    ->maxWidth(640)
    ->render($url);
```

- **Built-in providers:** YouTube, Vimeo, Spotify, SoundCloud, X, TikTok, Bluesky and Flickr. None needs an account
  or a token.
- **`providers([...])`** replaces the built-in list.
- **URL patterns** are `host/path`: a host starting with `*.` matches the domain and its subdomains, and `*` in the
  path matches anything.
- **Endpoints** must be https. The client only ever requests the endpoint (never the URL being embedded), and doesn't
  follow redirects.
- **`iframe()`** names the hosts the provider's player may load from, and **`script()`** is the script that turns its
  `<blockquote>` into an embed.
- **`privacyEnhancedYouTube()`** plays YouTube from `www.youtube-nocookie.com`.

Publish the config to change the app's defaults: the store, how long answers are kept, the timeout, `maxwidth`,
discovery and privacy-enhanced YouTube.

```bash
php artisan vendor:publish --tag=oembed-config
```

## Storage

Answers are kept between lookups: for a week (`oembed.ttl`), and a failed lookup for ten minutes
(`oembed.failure_ttl`), so a deleted video isn't asked about on every page view. Two stores come with the package:

- **`cache`** (the default) keeps answers in a Laravel cache store: `OEMBED_CACHE_STORE`, or your default store.
- **`database`** keeps them in an `oembed_responses` table, one row per URL with the answer as JSON. The migration
  isn't run from the package; publish it, then migrate:

  ```bash
  php artisan vendor:publish --tag=oembed-migrations
  php artisan migrate
  ```

  Then set `OEMBED_STORE=database`. Expired rows are ignored, and `php artisan oembed:prune` deletes them, so
  schedule it daily: `Schedule::command('oembed:prune')->daily();`

One embedder can use another store with `->store('database')`. To keep answers somewhere else, implement
`Webwizardsusa\OEmbed\Stores\EmbedStore` (`getMany()`, `put()` and `forget()`), add it, and name it in
`OEMBED_STORE` or `->store()`:

```php
use Webwizardsusa\OEmbed\Facades\OEmbed;

// In a service provider's boot():
OEmbed::extend('redis', fn ($app) => new RedisEmbedStore($app['redis']->connection()));
```

## Discovery

Sites that aren't providers can still be embedded if their pages name an oEmbed endpoint, as WordPress sites and
many others do. Turn it on with `discovery()` or `oembed.discovery`. It's off by default, because the server then
fetches pages that people link to. It comes with some limits:

- **Private networks are never reached.** Every request (the page, each redirect and the endpoint) goes through
  `UrlGuard`. That allows only http(s) on the standard ports, with no credentials in the URL, to hosts whose every
  address is public. So it refuses loopback, private, link-local (including cloud metadata at `169.254.169.254`),
  shared and reserved ranges, IPv4 inside IPv6, numeric disguises like `2130706433` or `127.1`, and local names like
  `localhost` or `*.internal`. Each request is pinned to the address that was checked, and redirects are followed by
  hand (at most 3), each one checked again.
- **Only what's needed is read.** Pages must be HTML and at most 1 MB, and only their `<head>` is parsed.
- **A discovered site doesn't get to choose a player's host.** A player is shown only when it comes from the page's
  own host, in a `sandbox` that stops it navigating your page. Anything else from a discovered site shows as the link.
- **Each lookup discovers at most 5 uncached pages**, one after another.

## Filament

```php
use Webwizardsusa\OEmbed\Filament\Forms\Components\OEmbedInput;
use Webwizardsusa\OEmbed\Filament\Infolists\Components\OEmbedEntry;
use Webwizardsusa\OEmbed\Filament\Tables\Columns\OEmbedColumn;

OEmbedInput::make('video_url')->required();                  // form
OEmbedEntry::make('video_url');                              // infolist
OEmbedColumn::make('video_url');                             // table
```

The field stores the URL as a string, so a `string` column is all it needs.

- **`OEmbedInput`** refuses URLs that can't be embedded, and shows a preview under the field: a card with the
  thumbnail and title, `->preview('embed')` for the player itself, or `->preview(false)` for none. `helperText()`
  shows above the preview. The `embed` preview runs no provider scripts in the panel: posts that need one (X,
  Bluesky) show their card.
- **`OEmbedEntry`** shows the embed, or its link when there is nothing to embed.
- **`OEmbedColumn`** shows the thumbnail and title. It looks up every row's URL on the page at once.

Each takes the same settings: `providers()`, `withProviders()`, `withoutProviders()`, `discovery()` and
`privacyEnhancedYouTube()`. Anything else goes through `embedder()`, because Filament components already have a
`maxWidth()`:

```php
OEmbedInput::make('video_url')->discovery()->embedder(fn (Embedder $embedder) => $embedder->maxWidth(640));
```

## Content Security Policy

Lookups happen on the server, so they need nothing from a CSP. Pages that show embeds load the providers' players,
scripts and images, so if your site sends a `Content-Security-Policy` header, allow the providers you use:

| Provider | `frame-src` | `script-src` | `connect-src` | `img-src` |
|---|---|---|---|---|
| YouTube | `www.youtube.com`, `www.youtube-nocookie.com` | | | |
| Vimeo | `player.vimeo.com` | | | |
| Spotify | `open.spotify.com` | | | |
| SoundCloud | `w.soundcloud.com` | | | |
| TikTok | `www.tiktok.com` | | | |
| X | `platform.twitter.com`, `platform.x.com` | `platform.x.com`, `platform.twitter.com` | `syndication.twitter.com`, `cdn.syndication.twimg.com` | |
| Bluesky | `embed.bsky.app` | `embed.bsky.app` | | |
| Flickr | | | | `*.staticflickr.com` |

All of these are `https:`. Preview cards and the table column also show the thumbnail images providers answer with,
such as `i.ytimg.com`. Providers change their hosts from time to time, so check the browser console for CSP errors
after adding or updating one.

## Testing

```bash
composer test
```
