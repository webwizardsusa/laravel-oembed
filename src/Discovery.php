<?php

namespace Webwizardsusa\OEmbed;

use DOMDocument;
use DOMElement;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;
use Webwizardsusa\OEmbed\Support\Url;

/**
 * oEmbed discovery for URLs no provider takes: fetch the page, find its
 * `<link rel="alternate" type="application/json+oembed" href="…">`, and ask that endpoint about the page.
 *
 * Every request (the page, each redirect, the endpoint the page names) goes through {@see UrlGuard} and is pinned
 * to the address it checked. Redirects are followed by hand, at most {@see MAX_REDIRECTS}, so each one is checked
 * too. Only the start of a page is read, and only HTML; answers larger than {@see OEmbedClient::MAX_RESPONSE_BYTES}
 * are refused.
 */
class Discovery
{
    public const int MAX_REDIRECTS = 3;

    /**
     * Pages larger than this are not read.
     */
    public const int MAX_PAGE_BYTES = 1024 * 1024;

    /**
     * The `<link>` belongs in the `<head>`, so only the head is parsed, or this much of the page without one.
     */
    public const int MAX_HEAD_BYTES = 256 * 1024;

    protected const string USER_AGENT = 'Mozilla/5.0 (compatible; LaravelOEmbed; +https://github.com/webwizardsusa/laravel-oembed)';

    public function __construct(
        protected UrlGuard $guard,
        protected int $timeout = 3,
    ) {}

    public static function fromConfig(): static
    {
        return new static(app(UrlGuard::class), (int) config('oembed.timeout', 3));
    }

    public function discover(string $url, ?int $maxWidth = null): ?OEmbedResponse
    {
        try {
            $page = $this->get($url, ['text/html', 'application/xhtml+xml'], static::MAX_PAGE_BYTES);

            if ($page === null) {
                return null;
            }

            $head = stristr($page['body'], '</head>', true);
            $endpoint = $this->endpointIn(substr($head === false ? $page['body'] : $head, 0, static::MAX_HEAD_BYTES), $page['url']);

            if ($endpoint === null) {
                return null;
            }

            $answer = $this->get(static::withMaxWidth($endpoint, $maxWidth), ['application/json', 'text/json', 'text/javascript', 'text/plain'], OEmbedClient::MAX_RESPONSE_BYTES);
            $data = $answer === null ? null : json_decode($answer['body'], true);

            return is_array($data) ? OEmbedResponse::fromArray($data) : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * The oEmbed endpoint a page names, made absolute against the page's URL. JSON only: XML oEmbed is not read.
     */
    public function endpointIn(string $html, string $pageUrl): ?string
    {
        $document = new DOMDocument;
        $document->loadHTML('<?xml encoding="utf-8">'.$html, LIBXML_NOERROR | LIBXML_NONET);

        foreach ($document->getElementsByTagName('link') as $link) {
            if (! $link instanceof DOMElement) {
                continue;
            }

            $rel = preg_split('/\s+/', strtolower(trim($link->getAttribute('rel')))) ?: [];
            $type = strtolower(trim($link->getAttribute('type')));

            if (in_array('alternate', $rel, true) && in_array($type, ['application/json+oembed', 'text/json+oembed'], true)) {
                return static::absoluteUrl(trim($link->getAttribute('href')), $pageUrl);
            }
        }

        return null;
    }

    /**
     * Fetches a URL through the guard, following redirects by hand.
     *
     * @param  array<string>  $contentTypes
     * @return array{url: string, body: string} | null
     */
    protected function get(string $url, array $contentTypes, int $maxBytes): ?array
    {
        for ($redirects = 0; $redirects <= static::MAX_REDIRECTS; $redirects++) {
            $target = Url::isAllowed($url) ? $this->guard->check($url) : null;

            if ($target === null) {
                return null;
            }

            $response = $this->request($url, $target, $maxBytes);

            if ($response->redirect()) {
                $location = static::absoluteUrl((string) $response->header('Location'), $url);

                if ($location === null) {
                    return null;
                }

                $url = $location;

                continue;
            }

            $type = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));

            if (! $response->successful() || ! in_array($type, $contentTypes, true) || strlen($response->body()) > $maxBytes + 8192) {
                return null;
            }

            return ['url' => $url, 'body' => $response->body()];
        }

        return null;
    }

    /**
     * @param  array{host: string, port: int, ip: string}  $target
     */
    protected function request(string $url, array $target, int $maxBytes): Response
    {
        return Http::withUserAgent(static::USER_AGENT)
            ->timeout($this->timeout)
            ->connectTimeout($this->timeout)
            ->withOptions([
                'allow_redirects' => false,
                'curl' => UrlGuard::pinnedCurlOptions($target),
                // Stop reading a body that grows past the limit; a page's <link> is near its start.
                'progress' => static function (int $total, int $downloaded) use ($maxBytes): void {
                    if ($downloaded > $maxBytes + 8192) {
                        throw new RuntimeException('The response is too large.');
                    }
                },
            ])
            ->get($url);
    }

    protected static function withMaxWidth(string $endpoint, ?int $maxWidth): string
    {
        if ($maxWidth === null || preg_match('/[?&]maxwidth=/i', $endpoint) === 1) {
            return $endpoint;
        }

        return $endpoint.(str_contains($endpoint, '?') ? '&' : '?').'maxwidth='.$maxWidth;
    }

    /**
     * An absolute http(s) URL for a link found on a page: absolute, scheme-relative (`//host/path`), root-relative
     * (`/path`) or relative to the page (`path`). Anything else answers null.
     */
    protected static function absoluteUrl(string $href, string $base): ?string
    {
        $href = html_entity_decode($href, ENT_QUOTES | ENT_HTML5);

        if ($href === '' || preg_match('/[\x00-\x20\x7F]/', $href) === 1) {
            return null;
        }

        if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $href) === 1) {
            return Url::isAllowed($href) ? $href : null;
        }

        $parts = parse_url($base);

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        if (str_starts_with($href, '//')) {
            return "{$parts['scheme']}:{$href}";
        }

        $origin = "{$parts['scheme']}://{$parts['host']}".(isset($parts['port']) ? ":{$parts['port']}" : '');

        if (str_starts_with($href, '/')) {
            return $origin.$href;
        }

        $directory = preg_replace('#/[^/]*$#', '/', $parts['path'] ?? '/') ?: '/';

        return $origin.$directory.$href;
    }
}
