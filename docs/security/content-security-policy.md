---
title: Content Security Policy
weight: 30
description: The hosts pages that show embeds need to allow in a Content-Security-Policy header.
---

Lookups happen on the server, so they need nothing from a CSP. Pages that **show** embeds load the providers' players,
scripts and images in the browser. If your site sends a `Content-Security-Policy` header, allow the providers you use.

## Built-in providers

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

All of these are `https:`. For example, a site showing YouTube and Vimeo videos:

```html
<meta http-equiv="Content-Security-Policy"
      content="frame-src https://www.youtube.com https://www.youtube-nocookie.com https://player.vimeo.com">
```

Providers change their hosts from time to time, and a provider's script may load more from its own domains: the X
entries are what its script loaded when this was written. Check the browser console for CSP errors after adding or
updating a provider.

## Thumbnails

The Filament form field's preview card and the table column show the thumbnail images providers answer with, such as
`i.ytimg.com` for YouTube or `i.vimeocdn.com` for Vimeo. If your panel sends a CSP, allow those hosts in `img-src`,
or allow `https:` images.

## Custom providers

For a provider you add, `frame-src` needs its `iframe()` hosts, and `script-src` the host of its `script()`, plus
whatever that script loads. Photos need the host of the photo URLs it answers with in `img-src`.

## Discovered pages

With [discovery](discovery.md), a player may come from any discovered page's host, so you can't list them in advance.
Either allow `frame-src https:`, or accept that players from hosts you haven't listed are blocked by the browser.
