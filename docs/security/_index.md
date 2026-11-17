---
title: Security
weight: 60
---

Embedding means printing something a third party decided, for a URL someone else typed. The package is built so that
neither can put markup or scripts of their choosing on your pages, or point your server at your own network:

- [How embeds are rendered](rendering.md): the provider's HTML is parsed and rebuilt from an allowlist, never printed.
- [Discovery](discovery.md): the optional lookup of sites that aren't providers, and the guard that keeps it off
  private networks.
- [Content Security Policy](content-security-policy.md): the hosts your pages need to allow for the built-in providers.

## Hard limits

These limits are built in and can't be configured:

| Limit | Value |
|---|---|
| URL length | 2048 characters. Longer URLs are never taken by a provider or discovery. |
| URL schemes | Absolute `http` and `https` only, everywhere: the URL being embedded, thumbnails, photo URLs, links in posts. Control characters and spaces browsers ignore are removed before the scheme is read, so `java\tscript:` is still refused. |
| oEmbed answer size | 512 KB, from a provider or a discovered endpoint. |
| `html` in an answer | 100,000 characters. A `video` or `rich` answer with more has no markup and isn't used. |
| Text in an answer | Trimmed and cut to 500 characters (title, author name, provider name). |
| Sizes in an answer | Whole numbers from 1 to 10,000; anything else is dropped. |
| Provider endpoints and scripts | Must be `https`. Redirects from an endpoint are not followed. |
| Pooled requests | At most 10 at a time. |
| Discovered page size | 1 MB; only the `<head>` (or the first 256 KB without one) is parsed. |
| Discovery redirects | 3, each one checked again. |
| Pages discovered per lookup | 5 uncached pages per `resolveMany()` call. |

The request timeout is the one setting here you control (`oembed.timeout`, 3 seconds by default).
