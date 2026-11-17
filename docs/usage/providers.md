---
title: Built-in providers
weight: 30
description: The providers every embedder offers, the URLs each takes and how each one renders.
---

Every embedder starts with these eight providers. None needs an account or an access token. Remove one with
`withoutProviders(['name'])`, replace one by adding a provider with the same name, or replace the whole list with
`providers([...])` (see [Changing settings](embedder.md#changing-settings)).

| Name | Label | URL patterns | Renders as |
|---|---|---|---|
| `youtube` | YouTube | `*.youtube.com/watch*`, `*.youtube.com/shorts/*`, `*.youtube.com/live/*`, `*.youtube.com/playlist*`, `youtu.be/*` | Player from `www.youtube.com` or `www.youtube-nocookie.com` |
| `vimeo` | Vimeo | `vimeo.com/*`, `www.vimeo.com/*`, `player.vimeo.com/video/*` | Player from `player.vimeo.com` |
| `spotify` | Spotify | `open.spotify.com/*` | Player from `open.spotify.com` |
| `soundcloud` | SoundCloud | `soundcloud.com/*`, `www.soundcloud.com/*`, `m.soundcloud.com/*`, `on.soundcloud.com/*` | Player from `w.soundcloud.com` |
| `x` | X | `*.twitter.com/*/status/*`, `*.x.com/*/status/*` | Post, with `https://platform.x.com/widgets.js` |
| `tiktok` | TikTok | `*.tiktok.com/@*/video/*` | Player from `www.tiktok.com` |
| `bluesky` | Bluesky | `bsky.app/profile/*/post/*` | Post, with `https://embed.bsky.app/static/embed.js` |
| `flickr` | Flickr | `*.flickr.com/photos/*`, `flic.kr/p/*` | Photo |

The endpoints they ask:

| Name | oEmbed endpoint |
|---|---|
| `youtube` | `https://www.youtube.com/oembed` |
| `vimeo` | `https://vimeo.com/api/oembed.json` |
| `spotify` | `https://open.spotify.com/oembed` |
| `soundcloud` | `https://soundcloud.com/oembed` |
| `x` | `https://publish.x.com/oembed` |
| `tiktok` | `https://www.tiktok.com/oembed` |
| `bluesky` | `https://embed.bsky.app/oembed` |
| `flickr` | `https://www.flickr.com/services/oembed/` |

## Reading the patterns

A pattern is `host/path`:

- A host starting with `*.` matches that domain and every subdomain of it, so `*.youtube.com` takes `youtube.com`,
  `www.youtube.com` and `m.youtube.com`.
- `*` in the path matches anything, including slashes.
- The query string is not matched, so `*.youtube.com/watch*` takes `https://www.youtube.com/watch?v=…`.
- Only absolute `http` and `https` URLs without credentials can match, and URLs longer than 2048 characters never do.

When several providers' patterns match a URL, the first one in the list wins. Built-in providers come first, in the
order above; providers added with `withProviders()` come after them, unless they replace a built-in one, in which
case they take its place.

## How each kind renders

- **Players**: the first `<iframe>` in the answer, kept only when its `src` is `https` on one of the hosts in the
  table. With [`privacyEnhancedYouTube()`](embedder.md#changing-settings), a `www.youtube.com` player is switched to
  `www.youtube-nocookie.com`.
- **Posts**: the provider's `<blockquote>`, reduced to text, links and a few inline tags, followed by the script in
  the table. A script in the answer itself is never used.
- **Photos**: an `<img>` of the answer's photo URL, linking to the page that was embedded.

When the answer doesn't fit, such as a YouTube answer whose iframe points elsewhere, the embed shows as its link. See
[How embeds are rendered](../security/rendering.md) for every rule.

## Hosts to allow in a CSP

If your pages send a `Content-Security-Policy`, each provider you show needs its hosts allowed. The table is in
[Content Security Policy](../security/content-security-policy.md).
