---
title: Styling
weight: 50
description: The CSS classes rendered embeds and the Filament components use, and how to change the frame's class.
---

## The stylesheet

`OEmbed::stylesheet()` returns the CSS rendered embeds need, from `resources/css/oembed.css`. It is small and only
sets layout: players fill their container's width and keep their aspect ratio, photos scale down, and captions are
smaller and lighter. Print it as is, copy it into your own CSS, or write your own rules for the classes below.

## Rendered embeds

```html
<figure class="oembed" data-kind="video">
  <div class="oembed-frame" style="aspect-ratio: 16 / 9">
    <iframe …></iframe>
  </div>
  <figcaption>…</figcaption>
</figure>
```

| Selector | |
|---|---|
| `.oembed` | Every embed's `<figure>`, including the link shown when there is nothing to embed. |
| `.oembed[data-kind="video"]`, `[data-kind="rich"]`, `[data-kind="photo"]` | The kind of embed. A link has no `data-kind`. |
| `.oembed-frame` | The wrapper around a player's `<iframe>`. It has an inline `aspect-ratio` for videos with a known size. |
| `.oembed-frame[style*="aspect-ratio"] iframe` | A video player, which fills the wrapper's height. |
| `.oembed blockquote` | A post, before its provider's script turns it into a widget. |
| `.oembed[data-kind="photo"] img` | A photo. |
| `.oembed figcaption` | The caption. |

For example, to centre videos and cap their width:

```css
.oembed[data-kind="video"] {
    max-width: 720px;
    margin-inline: auto;
}
```

## Filament components

Inside panels, `resources/css/filament.css` styles the form field's preview and the table column, using the panel's
gray palette in light and dark mode:

| Selector | |
|---|---|
| `.oembed-card` | The form field's preview card. |
| `.oembed-card img` | Its thumbnail. |
| `.oembed-card-body`, `.oembed-card-provider`, `.oembed-card-title`, `.oembed-card-url` | Its text. |
| `.oembed-preview` | The wrapper of the form field's `'embed'` preview. |
| `.oembed-column` | A table cell. |
| `.oembed-column img` | Its thumbnail. |
| `.oembed-column-text`, `.oembed-column-title`, `.oembed-column-url` | Its text. |

To change them, add rules to your panel's theme.

## Changing the frame's class: `markup($prefix)`

`Embedder::markup(string $classPrefix = 'oembed')` returns the `EmbedMarkup` builder that rebuilds providers' markup.
Its prefix names the player wrapper: `{$classPrefix}-frame`. The figure around it is written by whoever calls the
builder, so the prefix only matters when you build your own figure, as a package that renders embeds inside its own
content does. Rich Editor Plus Embed, for example, uses `markup('rep-embed')` to get `rep-embed-frame` wrappers:

```php
use Webwizardsusa\OEmbed\Facades\OEmbed;
use Webwizardsusa\OEmbed\Rendering\HtmlBuilder;

$embedder = OEmbed::privacyEnhancedYouTube();
$embed = $embedder->resolve($url);
$provider = $embed === null ? null : $embedder->providerFor($embed->url);

$display = $provider === null
    ? null
    : $embedder->markup('post-embed')->build($provider, $embed->response, $embed->url);

$html = $display === null
    ? null
    : HtmlBuilder::build(['figure', ['class' => 'post-embed', 'data-kind' => $display['kind']], ...$display['content']]);
```

`build()` returns `['kind' => …, 'content' => [...]]`, the elements that go inside the figure as
`['tag', $attributes, ...$children]` arrays, or `null` when the embed should show as its link.
`Webwizardsusa\OEmbed\Rendering\HtmlBuilder::build()` writes such an array as HTML, escaping every value. Don't
assemble the HTML yourself from the answer: going through these two keeps the [rendering rules](../security/rendering.md).

`providerFor()` answers the provider a URL is shown through: the one that takes it, or, with discovery, the one made
for its page (`$embed->provider` is `null` for those).
