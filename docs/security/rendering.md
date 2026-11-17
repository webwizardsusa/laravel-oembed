---
title: How embeds are rendered
weight: 10
description: How the package rebuilds a provider's markup from an allowlist instead of printing it.
---

An oEmbed answer's `html` is whatever the provider (or, with discovery, any site) sent. The package never prints it.
It parses it, keeps only the part each kind of embed needs, and writes new markup with allowlisted attributes. Every
text and attribute value it writes is escaped, and attributes named `on…` are never written.

The decision depends on the answer's `type` and on what the provider declares:

| Answer type | Provider has `iframe()` hosts | Provider has a `script()` | Result |
|---|---|---|---|
| `photo` | any | any | An `<img>` linking to the page |
| `video` or `rich` | yes | any | The provider's `<iframe>`, if its `src` passes |
| `video` or `rich` | no | yes | The provider's `<blockquote>` and its script |
| `video` or `rich` | no | no | The link |
| `link` | any | any | The link |

When a step fails, such as an iframe from the wrong host or no `<blockquote>` in the answer, the embed falls back to
the link: `<figure class="oembed"><a href="…">title or URL</a></figure>`.

## Players

The first `<iframe>` in the answer is used, and only when:

- its `src` is `https`, without credentials, and
- its host is **exactly** one of the provider's `iframe()` hosts (no wildcards).

The new iframe gets only these attributes:

| Attribute | Value |
|---|---|
| `src` | The checked `src`. With privacy-enhanced YouTube, `https://www.youtube.com/…` becomes `https://www.youtube-nocookie.com/…`. |
| `width`, `height` | From the iframe, or the answer, when they are whole numbers from 1 to 10,000. |
| `title` | From the iframe, or the answer's title. |
| `allow` | Only `accelerometer`, `autoplay`, `clipboard-write`, `encrypted-media`, `fullscreen`, `gyroscope`, `picture-in-picture` and `web-share`, from what the iframe asked for. |
| `allowfullscreen` | When the iframe had it or asked for `fullscreen`. |
| `loading` | `lazy` |
| `referrerpolicy` | `strict-origin-when-cross-origin` |
| `sandbox` | Only for providers with `sandboxIframes()` (and every discovered page): `allow-scripts allow-same-origin allow-popups allow-popups-to-escape-sandbox allow-presentation`. |

Anything else, such as `style`, `onload` or `srcdoc`, is dropped. The iframe is wrapped in
`<div class="oembed-frame">`. For a `video` with a width and height, the wrapper gets an `aspect-ratio` style so the
player keeps its shape as it scales; a portrait video also gets a `max-width` of its own width.

## Posts

For a provider with a `script()` and no iframe hosts, the first `<blockquote>` in the answer is used:

- Its `class` is kept when each class is a plain token (letters, digits, `-`, `_`), its `data-*` attributes are kept
  (as inert text), and its `cite` is kept when it is an `http(s)` URL. The provider's script finds the blockquote by
  these.
- Inside it, only `p`, `a`, `br`, `section`, `span`, `strong`, `em`, `b` and `i` are kept. Other elements are
  replaced by their text, and `script`, `style` and `template` are removed with their contents.
- Links keep only an `http(s)` `href`. Paragraphs keep only a valid `lang` and a `dir` of `ltr`, `rtl` or `auto`.

It is followed by `<script async src="…" charset="utf-8">` with the **provider's** script URL, from your code. A script
in the answer is never used, so a provider (or someone who can change its answers) can't choose what runs on your
page.

## Photos

A `photo` answer becomes:

```html
<a href="{the embedded URL}"><img src="{the answer's url}" alt="{title}" width="…" height="…" loading="lazy"></a>
```

The photo URL must be an absolute `http(s)` URL of at most 2048 characters, or the answer isn't used at all.

## Links

A `link` answer, an answer that fails the checks above, a failed lookup and a URL no provider takes all render as a
link to the URL, with the answer's title as its text when there is one. The link's `href` is the URL being embedded,
which has already been checked to be `http(s)`.

## Discovered pages

A page found by [discovery](discovery.md) is shown through a provider made for it: its player may only come from the
page's own host, and it always plays in the sandbox above, so it can't navigate your page. It has no script, so posts
that need one show as their link. Photos are shown.

## Adding your own rules

The rules above apply to [custom providers](../extending/custom-providers.md) too: what a provider can render is
decided by its `iframe()` hosts and `script()`, which you declare in code, never by its answers.
