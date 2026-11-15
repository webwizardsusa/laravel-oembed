<?php

namespace Webwizardsusa\OEmbed\Stores;

/**
 * The key an answer is stored under: the provider (`discovered` for a page found by discovery), the URL and the
 * `maxwidth` it was asked for with, since providers answer differently for each.
 */
final class StoreKey
{
    public static function make(string $provider, string $url, ?int $maxWidth): string
    {
        return sha1("{$provider}|{$url}|{$maxWidth}");
    }
}
