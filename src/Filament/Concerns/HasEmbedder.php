<?php

namespace Webwizardsusa\OEmbed\Filament\Concerns;

use Closure;
use Webwizardsusa\OEmbed\Embedder;
use Webwizardsusa\OEmbed\Providers\Provider;

/**
 * The embed settings of a Filament field, entry or column. They start from the app's defaults (`config/oembed.php`).
 * Settings without a method of their own here, such as `maxWidth()` (which Filament components already have), go
 * through {@see Embedder()}.
 */
trait HasEmbedder
{
    protected ?Embedder $embedder = null;

    /**
     * Another embedder, or a callback that changes this one: `->embedder(fn (Embedder $embedder) => $embedder->maxWidth(640))`.
     *
     * @param  Embedder | (Closure(Embedder): mixed)  $embedder
     */
    public function embedder(Embedder|Closure $embedder): static
    {
        if ($embedder instanceof Closure) {
            $embedder($this->getEmbedder());
        } else {
            $this->embedder = $embedder;
        }

        return $this;
    }

    /**
     * @param  array<Provider>  $providers
     */
    public function providers(array $providers): static
    {
        $this->getEmbedder()->providers($providers);

        return $this;
    }

    /**
     * @param  array<Provider>  $providers
     */
    public function withProviders(array $providers): static
    {
        $this->getEmbedder()->withProviders($providers);

        return $this;
    }

    /**
     * @param  array<string>  $names
     */
    public function withoutProviders(array $names): static
    {
        $this->getEmbedder()->withoutProviders($names);

        return $this;
    }

    public function discovery(bool $condition = true): static
    {
        $this->getEmbedder()->discovery($condition);

        return $this;
    }

    public function privacyEnhancedYouTube(bool $condition = true): static
    {
        $this->getEmbedder()->privacyEnhancedYouTube($condition);

        return $this;
    }

    public function getEmbedder(): Embedder
    {
        return $this->embedder ??= Embedder::make();
    }
}
