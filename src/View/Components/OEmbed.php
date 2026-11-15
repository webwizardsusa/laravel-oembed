<?php

namespace Webwizardsusa\OEmbed\View\Components;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Component;
use Webwizardsusa\OEmbed\Embedder;

/**
 * `<x-oembed :url="$post->video_url" caption="…" />`: the embed for a URL, as {@see Embedder::render()} writes it.
 * Pass `:embedder` to use other settings than the app's defaults. Include `OEmbed::stylesheet()` on the page.
 */
class OEmbed extends Component
{
    public function __construct(
        public ?string $url = null,
        public ?string $caption = null,
        public ?Embedder $embedder = null,
    ) {}

    public function render(): Htmlable
    {
        return ($this->embedder ?? Embedder::make())->render($this->url, $this->caption);
    }
}
