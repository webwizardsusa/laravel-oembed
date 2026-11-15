<?php

namespace Webwizardsusa\OEmbed\Providers;

use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * A site whose URLs can be embedded: the URLs it answers for, its oEmbed endpoint, and what its markup may load.
 *
 * URL patterns are `host/path`, such as `youtu.be/*` or `*.youtube.com/watch*`. A host starting with `*.` matches that
 * domain and every subdomain of it; `*` in the path matches anything. The query string is not matched.
 */
class Provider
{
    /**
     * @var array<array{host: string, path: string}>
     */
    protected array $patterns = [];

    protected ?string $endpoint = null;

    /**
     * @var array<string, string>
     */
    protected array $query = [];

    /**
     * @var array<string>
     */
    protected array $iframeHosts = [];

    protected ?string $scriptUrl = null;

    protected ?string $label = null;

    protected bool $sandboxesIframes = false;

    final public function __construct(protected string $name)
    {
        if (preg_match('/^[a-z0-9][a-z0-9-]*$/', $name) !== 1) {
            throw new InvalidArgumentException("A provider name must be lowercase letters, digits and dashes; [{$name}] is not.");
        }
    }

    public static function make(string $name): static
    {
        return new static($name);
    }

    /**
     * The provider for a page found by discovery: its player may only come from the page's own host, and plays in a
     * sandbox that keeps it from navigating the page it's shown on. It has no endpoint or URL patterns of its own.
     */
    public static function discovered(string $url): ?static
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return null;
        }

        $host = rtrim(strtolower($host), '.');

        return static::make('discovered')->label($host)->iframe([$host])->sandboxIframes();
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @param  array<string>  $patterns
     */
    public function urls(array $patterns): static
    {
        $this->patterns = array_map(static function (string $pattern): array {
            [$host, $path] = array_pad(explode('/', $pattern, 2), 2, '*');
            $host = strtolower($host);
            $domain = str_starts_with($host, '*.') ? substr($host, 2) : $host;

            if ($domain === '' || str_contains($domain, '*')) {
                throw new InvalidArgumentException("[{$pattern}] is not a URL pattern: a host may only start with [*.].");
            }

            return ['host' => $host, 'path' => '/'.$path];
        }, $patterns);

        return $this;
    }

    /**
     * The provider's oEmbed endpoint, which must be https. `$query` is sent with every request, such as an access token.
     *
     * @param  array<string, string>  $query
     */
    public function endpoint(string $url, array $query = []): static
    {
        if (! str_starts_with(strtolower($url), 'https://') || ! is_string(parse_url($url, PHP_URL_HOST))) {
            throw new InvalidArgumentException("The oEmbed endpoint of [{$this->name}] must be an https URL.");
        }

        $this->endpoint = $url;
        $this->query = $query;

        return $this;
    }

    /**
     * The provider's markup is an `<iframe>` from one of these hosts. Any other iframe in its oEmbed answer is refused.
     *
     * @param  array<string>  $hosts
     */
    public function iframe(array $hosts): static
    {
        $this->iframeHosts = array_map(strtolower(...), $hosts);

        return $this;
    }

    /**
     * The provider's markup is a `<blockquote>` turned into an embed by this script. The script always comes from here,
     * never from an oEmbed answer.
     */
    public function script(string $url): static
    {
        if (! str_starts_with(strtolower($url), 'https://')) {
            throw new InvalidArgumentException("The embed script of [{$this->name}] must be an https URL.");
        }

        $this->scriptUrl = $url;

        return $this;
    }

    /**
     * Plays the provider's iframes with a `sandbox` attribute: scripts and popups run, but the player can't navigate
     * the page it's shown on.
     */
    public function sandboxIframes(bool $condition = true): static
    {
        $this->sandboxesIframes = $condition;

        return $this;
    }

    public function sandboxesIframes(): bool
    {
        return $this->sandboxesIframes;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label ?? Str::headline($this->name);
    }

    public function getEndpoint(): ?string
    {
        return $this->endpoint;
    }

    /**
     * @return array<string, string>
     */
    public function getQuery(): array
    {
        return $this->query;
    }

    /**
     * @return array<string>
     */
    public function getIframeHosts(): array
    {
        return $this->iframeHosts;
    }

    public function getScriptUrl(): ?string
    {
        return $this->scriptUrl;
    }

    /**
     * The hosts the patterns name, with `*.` kept, for the editor to spot pasted URLs that may be embeddable.
     *
     * @return array<string>
     */
    public function getHosts(): array
    {
        return array_values(array_unique(array_column($this->patterns, 'host')));
    }

    /**
     * Whether the URL is one of this provider's. Only absolute http(s) URLs without credentials can match.
     */
    public function matches(string $url): bool
    {
        $parts = parse_url(trim($url));

        if (! is_array($parts)
            || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            || ! isset($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])) {
            return false;
        }

        $host = rtrim(strtolower($parts['host']), '.');
        $path = $parts['path'] ?? '/';

        foreach ($this->patterns as $pattern) {
            if (static::hostMatches($pattern['host'], $host) && Str::is($pattern['path'], $path)) {
                return true;
            }
        }

        return false;
    }

    protected static function hostMatches(string $pattern, string $host): bool
    {
        if (! str_starts_with($pattern, '*.')) {
            return $host === $pattern;
        }

        $domain = substr($pattern, 2);

        return $host === $domain || str_ends_with($host, '.'.$domain);
    }
}
