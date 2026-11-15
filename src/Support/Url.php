<?php

namespace Webwizardsusa\OEmbed\Support;

/**
 * URL checks shared by the lookups and the markup they build.
 */
class Url
{
    /**
     * An absolute http(s) URL. Any other scheme (`javascript:`, `data:`, `file:`) or a relative URL is refused, however
     * it is disguised: control characters and spaces are removed before the scheme is read, because browsers ignore
     * them, so `java\tscript:` is still `javascript:`.
     */
    public static function isAllowed(mixed $url): bool
    {
        if (! is_string($url) || preg_match('#^\s*https?://#i', $url) !== 1) {
            return false;
        }

        $cleaned = preg_replace('/[\x{0000}-\x{0020}\x{007F}-\x{00A0}\x{1680}\x{180E}\x{2000}-\x{2029}\x{205F}\x{3000}]/u', '', $url);

        return is_string($cleaned) && preg_match('/^https?:/i', $cleaned) === 1;
    }
}
