---
title: Discovery
weight: 20
description: Embedding sites that aren't providers through oEmbed discovery, and the guard that keeps it off private networks.
---

## What discovery does

Sites that aren't providers can still be embedded if their pages name an oEmbed endpoint, as WordPress sites and many
others do:

```html
<link rel="alternate" type="application/json+oembed" href="https://example.com/oembed?url=…">
```

With discovery on, a URL no provider takes is looked up by fetching its page, finding that `<link>`, and asking the
endpoint it names. Turn it on per embedder or for the whole app:

```php
use Webwizardsusa\OEmbed\Facades\OEmbed;

OEmbed::discovery()->render($url);
```

```php
// config/oembed.php
'discovery' => true,
```

Filament components have `->discovery()` too.

> [!IMPORTANT]
> Discovery is off by default because your server then fetches **any page someone links to**. Turn it on only where
> that is acceptable, such as for trusted editors, and read the limits below.

Providers always come first: a URL a provider takes is asked of that provider, never discovered.

## What is read

- **JSON only.** Only `application/json+oembed` (or `text/json+oembed`) links are used. XML oEmbed endpoints are
  ignored.
- **HTML pages only**, served as `text/html` or `application/xhtml+xml`, of at most 1 MB. The download stops once it
  grows past that.
- **Only the `<head>`** is parsed for the link, or the first 256 KB of a page without a `</head>`.
- The endpoint's answer must be JSON (`application/json`, `text/json`, `text/javascript` or `text/plain`), at most
  512 KB. The configured `maxwidth` is added to the endpoint's query unless it already has one.
- The endpoint URL may be relative to the page; it is made absolute against the page's URL.

Answers and failures are [stored](../usage/stores.md) like providers' answers, under the provider name `discovered`.

## Private networks are never reached

Every request discovery makes (the page, each redirect, and the endpoint the page names) goes through
`Webwizardsusa\OEmbed\UrlGuard` first. It allows only:

- `http` or `https`, on the standard port (80 or 443),
- with no username or password in the URL,
- to a host whose **every** address is public.

So it refuses:

- loopback, private, link-local (including cloud metadata at `169.254.169.254`), shared (`100.64.0.0/10`),
  documentation, benchmarking and reserved ranges, for IPv4 and IPv6;
- IPv4 addresses inside IPv6, such as `::ffff:127.0.0.1`;
- hosts that only look like numbers, such as `2130706433`, `0x7f.1` or `127.1`;
- local names: `localhost`, and names ending in `.local`, `.internal`, `.intranet`, `.lan`, `.home`, `.corp`,
  `.localdomain` or `.home.arpa`.

Each request is **pinned** to the address the guard checked, so DNS can't answer with a public address for the check
and a private one when the request connects. Redirects are followed by hand, at most 3, and each new URL is checked
again.

Provider lookups don't go through the guard: they only ever request the provider's own `https` endpoint, from your
code, and don't follow redirects.

## A discovered site doesn't choose a player's host

A discovered answer is shown through a provider made for the page:

- A player is shown only when its iframe comes from **the page's own host**, and it plays in a `sandbox` that stops it
  navigating your page.
- Posts that need a script show as their link: a discovered site never gets a script on your page.
- Photos are shown.
- Anything else shows as the link.

See [How embeds are rendered](rendering.md#discovered-pages).

## At most five pages per lookup

Pages are fetched one after another, so each `resolveMany()` call discovers at most **5 uncached pages**. The other
URLs answer `null` for that call (they show as links) and are discovered by a later one. Stored answers don't count
towards the five.

This keeps a page with many unknown links from stalling on dozens of sequential fetches. A Filament table column or a
page that renders many embeds fills in over a few requests.

## Testing code that uses discovery

The guard resolves hosts through DNS, which tests shouldn't depend on. See
[Testing](../extending/testing.md) for rebinding it with fixed addresses.
