<?php

namespace Webwizardsusa\OEmbed;

use Illuminate\Contracts\Support\Htmlable;
use Webwizardsusa\OEmbed\Providers\Provider;

/**
 * A URL that resolved to something embeddable: its provider (null for a page found by discovery) and the provider's
 * checked answer. {@see toHtml()} renders it as {@see Embedder::render()} does.
 */
final readonly class Embed implements Htmlable
{
    public function __construct(
        public string $url,
        public ?Provider $provider,
        public OEmbedResponse $response,
        protected Embedder $embedder,
    ) {}

    public function title(): ?string
    {
        return $this->response->title;
    }

    public function thumbnail(): ?string
    {
        return $this->response->thumbnailUrl;
    }

    /**
     * The provider's name, such as `youtube`, or null for a page found by discovery.
     */
    public function providerName(): ?string
    {
        return $this->provider?->getName();
    }

    /**
     * The provider's label, such as "YouTube", or what the page's answer calls its provider.
     */
    public function providerLabel(): ?string
    {
        return $this->provider?->getLabel() ?? $this->response->providerName;
    }

    public function toHtml(): string
    {
        return $this->embedder->renderAnswer($this->url, $this->response)->toHtml();
    }

    /**
     * @return array{url: string, provider: ?string, title: ?string, thumbnail: ?string}
     */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'provider' => $this->providerName(),
            'title' => $this->title(),
            'thumbnail' => $this->thumbnail(),
        ];
    }
}
