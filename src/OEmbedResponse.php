<?php

namespace Webwizardsusa\OEmbed;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Support\Arr;
use Illuminate\View\View;
use Webwizardsusa\OEmbed\Exceptions\ProviderNotFoundException;

class OEmbedResponse implements Arrayable, Renderable
{
    protected array $data;
    protected string $provider;
    protected string $url;

    protected string $embedCode;

    protected int $width = 0;
    protected int $height = 0;
    protected Carbon $retrievedAt;

    public function __construct(string $url, string $provider, array $data)
    {
        $this->retrievedAt = Carbon::now();
        $this->url = $url;
        $this->provider = $provider;
        $this->data = $data;
        $this->embedCode = (string)Arr::get($data, 'html', '');
        $this->width = (int)Arr::get($data, 'width', 0);
        $this->height = (int)Arr::get($data, 'height', 0);
    }

    public static function make(string $url, string $provider, array $data): static
    {
        return app(static::class, [
            'url' => $url,
            'provider' => $provider,
            'data' => $data
        ]);
    }

    public static function hydrate(array $data): static
    {
        $instance = static::make($data['url'], $data['provider'], $data['raw'])
            ->width($data['width'])
            ->height($data['height']);
        $instance->retrievedAt = Carbon::parse($data['retrieved_at']);
        return $instance;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function width(int $width): static
    {
        $this->width = $width;
        return $this;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function height(int $height): static
    {
        $this->height = $height;
        return $this;
    }

    public function embedCode(): string
    {
        return $this->embedCode;
    }

    public function setEmbedCode(string $embedCode): static
    {
        $this->embedCode = $embedCode;
        return $this;
    }


    public function get(string $key): mixed
    {
        return Arr::get($this->data, $key);
    }

    public function has(string $key): bool
    {
        return Arr::has($this->data, $key);
    }

    public function aspectRatio(): float
    {
        if ($this->width === 0 || $this->height === 0) {
            return 100;
        }
        return  ($this->height / $this->width) * 100;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function raw(): array
    {
        return $this->data;
    }

    public function retrievedAt(): Carbon
    {
        return $this->retrievedAt;
    }
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'provider' => $this->provider,
            'embed_code' => $this->embedCode,
            'raw' => $this->raw(),
            'width' => $this->width,
            'height' => $this->height,
            'retrieved_at' => $this->retrievedAt->toJSON()
        ];
    }

    public function render(bool $throw = false): mixed
    {
        $provider = app(OEmbed::class)->get($this->provider);
        if (!$provider) {
            throw_if($throw, new ProviderNotFoundException($this->provider));
            return null;
        }
        return $provider->render($this);
    }
}
