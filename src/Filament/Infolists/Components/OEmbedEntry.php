<?php

namespace Webwizardsusa\OEmbed\Filament\Infolists\Components;

use Filament\Infolists\Components\Entry;
use Filament\Support\Components\Contracts\HasEmbeddedView;
use Webwizardsusa\OEmbed\Filament\Concerns\HasEmbedder;

/**
 * Shows the embed for the entry's URL (the provider's player, post or photo), or its link when there is nothing to
 * embed.
 */
class OEmbedEntry extends Entry implements HasEmbeddedView
{
    use HasEmbedder;

    public function toEmbeddedHtml(): string
    {
        $state = $this->getState();

        return $this->wrapEmbeddedHtml($this->getEmbedder()->render(is_string($state) ? $state : null)->toHtml());
    }
}
