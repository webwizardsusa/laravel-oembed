<?php

namespace Webwizardsusa\OEmbed;

use Illuminate\Support\Arr;

class OEmbedUrl
{

    protected string $url;
    protected array $query;
    /**
     * @var mixed|string
     */
    protected mixed $host;
    /**
     * @var mixed|string
     */
    protected mixed $path;
    /**
     * @var mixed|string
     */
    protected mixed $fragment;
    /**
     * @var mixed|string
     */
    protected mixed $scheme;

    public function __construct(string $url)
    {
        $this->url = $url;
        $this->extract();
    }

    protected function extract(): void {
        $urlInfo = array_merge(['scheme' => '', 'host' => '', 'path' => '', 'query' => '', 'fragment' => ''], parse_url($this->url));
        $this->scheme = $urlInfo['scheme'];
        $this->host = $urlInfo['host'];
        $this->path = $urlInfo['path'];
        $this->fragment = $urlInfo['fragment'];
        if (!empty($urlInfo['query'])) {
            $query = [];
            parse_str($urlInfo['query'], $query);
            $this->query = $query;
        }

    }
    public static function make(string $url): static
    {
        return new static($url);
    }


    public function url(): string
    {
        return $this->url;
    }

    public function query(): array
    {
        return $this->query;
    }

    public function queryString(): string
    {
        return $this->query ? http_build_query($this->query) : '';
    }

    public function getQuery(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->query, $key, $default);
    }

    public function hasQuery(string $key): bool
    {
        return Arr::has($this->query, $key);
    }

    public function host(): mixed
    {
        return $this->host;
    }

    public function domainOf(string $needle, bool $caseInsensitive = true): bool
    {
        $pattern = '/^(.*?)' . preg_quote($needle, '/') . '$/';
        if ($caseInsensitive) {
            $pattern.='i';
        }
        return preg_match($pattern, $this->host) === 1;
    }

    public function isSecure(): bool
    {
        return strtolower($this->scheme) === 'https';
    }
    public function path(): mixed
    {
        return $this->path;
    }

    public function fragment(): mixed
    {
        return $this->fragment;
    }

    public function scheme(): mixed
    {
        return $this->scheme;
    }

    public function __toString(): string
    {
        return $this->url;
    }

}
