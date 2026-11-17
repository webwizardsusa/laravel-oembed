---
title: Form field
weight: 10
description: OEmbedInput, a URL field that refuses URLs that can't be embedded and previews the embed.
---

## Usage

```php
use Webwizardsusa\OEmbed\Filament\Forms\Components\OEmbedInput;

OEmbedInput::make('video_url')
    ->label('Video')
    ->required();
```

`OEmbedInput` is a Filament `TextInput` for a URL to embed. It stores the URL as a string, so a `string` column is all
it needs:

```php
$table->string('video_url', 2048)->nullable();
```

Out of the box the field:

- is a URL input (`url()`), at most 2048 characters (`maxLength(2048)`);
- updates on blur (`live(onBlur: true)`), so the preview appears when the user leaves the field;
- refuses URLs that can't be embedded;
- shows a preview of the embed below the field.

## Validation

A filled value must pass two checks, after Filament's own `url()` rule:

1. **A provider takes it** (or, with discovery, it is any `http(s)` URL). Otherwise: "This site can't be embedded."
2. **The lookup finds something to embed.** Otherwise: "Nothing could be embedded from this URL. Check that the page
   exists and is public."

The second check is a **live HTTP request** to the provider's oEmbed endpoint the first time a URL is seen, made while
the form validates. The answer is [stored](../usage/stores.md), so the preview and later renders read it back
instead of asking again. Keep this in mind for forms that are submitted often, or in tests: fake the provider with
`Http::fake()`.

Blank values pass, so use `required()` when the field must be filled. The messages come from the
`oembed::oembed.validation.unsupported` and `oembed::oembed.validation.unavailable` translation keys; publish the
translations to change them (see [Installation](../installation.md#publish-the-translations-optional)).

## Preview

Once a URL that can be embedded is entered, the field shows it below the input. Choose how with `preview()`:

```php
OEmbedInput::make('video_url')->preview('card');    // the default
OEmbedInput::make('video_url')->preview('embed');
OEmbedInput::make('video_url')->preview(false);
```

| Value | Shows |
|---|---|
| `'card'` | A card with the thumbnail, the provider's label, the title and the URL. Only the thumbnail is loaded from the provider. |
| `'embed'` | The embed itself, as [`render()`](../usage/embedder.md#rendering) writes it: the player, post or photo. |
| `false` | Nothing. |

Any other value turns the preview off. Nothing is shown while the field is empty, or when the URL has nothing to
embed (the validation error explains why).

With `'embed'`, the panel loads the providers' players, and X and Bluesky posts include their provider's script. If
your panel sends a Content Security Policy, it needs the hosts listed in
[Content Security Policy](../security/content-security-policy.md). With `'card'`, it only needs the thumbnail hosts in
`img-src`.

### Helper text and `belowContent()`

The preview is placed with Filament's `belowContent()`, so **don't call `belowContent()` on this field**: it would
replace the preview. `helperText()` still works; the field overrides it to show the text above the preview, in the
same space:

```php
OEmbedInput::make('video_url')->helperText('A YouTube or Vimeo link.');
```

## Embed settings

The field has the same settings as an embedder, applied to its validation and preview:

```php
use Webwizardsusa\OEmbed\Filament\Forms\Components\OEmbedInput;
use Webwizardsusa\OEmbed\Providers\Provider;

OEmbedInput::make('video_url')
    ->withProviders([
        Provider::make('loom')
            ->label('Loom')
            ->urls(['*.loom.com/share/*'])
            ->endpoint('https://www.loom.com/v1/oembed')
            ->iframe(['www.loom.com']),
    ])
    ->withoutProviders(['tiktok', 'x'])
    ->discovery()                 // accept any site that supports oEmbed discovery
    ->privacyEnhancedYouTube();   // for the 'embed' preview
```

| Method | |
|---|---|
| `providers(array $providers)` | Replaces the built-in providers. |
| `withProviders(array $providers)` | Adds providers, or replaces one with the same name. |
| `withoutProviders(array $names)` | Removes providers by name. |
| `discovery(bool $condition = true)` | Also accepts sites found by [oEmbed discovery](../security/discovery.md). |
| `privacyEnhancedYouTube(bool $condition = true)` | Plays YouTube from `www.youtube-nocookie.com`. |

Settings without a method of their own go through `embedder()`. This includes `maxWidth()` (Filament fields already
have a `maxWidth()` of their own, for the field's width) and `store()`:

```php
use Webwizardsusa\OEmbed\Embedder;

OEmbedInput::make('video_url')
    ->embedder(fn (Embedder $embedder) => $embedder->maxWidth(640)->store('database'));
```

`embedder()` takes either a closure, which is called once, straight away, with the field's embedder to change it, or
an `Embedder` instance to use instead:

```php
use Webwizardsusa\OEmbed\Facades\OEmbed;

OEmbedInput::make('video_url')->embedder(OEmbed::discovery()->maxWidth(640));
```

Methods called after `embedder($instance)` change that instance. The field's embedder starts from the app's defaults
in `config/oembed.php`.

> [!TIP]
> Give the field the same providers, `maxWidth()` and store as the place that renders the URL, such as an
> [`OEmbedEntry`](infolist-entry.md) or a `<x-oembed>` component. The answer the field stored during validation is then
> read back instead of being looked up again.

## Accepting only some providers

To accept only YouTube and Vimeo links, give the field just those providers. Built-in providers can be taken from
`BuiltInProviders::all()` by name:

```php
use Webwizardsusa\OEmbed\Providers\BuiltInProviders;
use Webwizardsusa\OEmbed\Providers\Provider;

OEmbedInput::make('video_url')->providers(array_values(array_filter(
    BuiltInProviders::all(),
    fn (Provider $provider): bool => in_array($provider->getName(), ['youtube', 'vimeo'], true),
)));
```

Or remove the ones you don't want with `withoutProviders([...])`.
