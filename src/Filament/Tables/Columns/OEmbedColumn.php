<?php

namespace Webwizardsusa\OEmbed\Filament\Tables\Columns;

use Filament\Support\Components\Contracts\HasEmbeddedView;
use Filament\Tables\Columns\Column;
use Webwizardsusa\OEmbed\Embed;
use Webwizardsusa\OEmbed\Filament\Concerns\HasEmbedder;
use Webwizardsusa\OEmbed\Rendering\HtmlBuilder;
use Webwizardsusa\OEmbed\Support\Url;

/**
 * The thumbnail and title of the embed for each row's URL, or the URL when there is nothing to embed. The first row
 * looks up the URLs of every record on the page at once, so a page of uncached URLs costs one pooled request rather
 * than one per row.
 */
class OEmbedColumn extends Column implements HasEmbeddedView
{
    use HasEmbedder;

    /**
     * @var array<string, ?Embed> | null
     */
    protected ?array $pageEmbeds = null;

    public function toEmbeddedHtml(): string
    {
        $state = $this->getState();

        if (! is_string($state) || ! Url::isAllowed($state)) {
            return '';
        }

        $url = trim($state);
        $pageEmbeds = $this->getPageEmbeds();
        $embed = array_key_exists($url, $pageEmbeds) ? $pageEmbeds[$url] : ($this->pageEmbeds[$url] = $this->getEmbedder()->resolve($url));

        return HtmlBuilder::build([
            'div',
            ['class' => 'oembed-column'],
            ...(Url::isAllowed($embed?->thumbnail()) ? [['img', ['src' => $embed->thumbnail(), 'alt' => '', 'loading' => 'lazy', 'referrerpolicy' => 'no-referrer']]] : []),
            ['div', ['class' => 'oembed-column-text'],
                ['span', ['class' => 'oembed-column-title'], $embed?->title() ?? $url],
                ...($embed?->title() !== null ? [['span', ['class' => 'oembed-column-url'], $url]] : []),
            ],
        ]);
    }

    /**
     * The embeds for the URLs of every record on the table's current page, looked up once.
     *
     * @return array<string, ?Embed>
     */
    protected function getPageEmbeds(): array
    {
        if ($this->pageEmbeds !== null) {
            return $this->pageEmbeds;
        }

        $urls = [];

        foreach ($this->getTable()->getRecords() as $record) {
            $url = data_get($record, $this->getName());

            if (is_string($url)) {
                $urls[] = $url;
            }
        }

        return $this->pageEmbeds = $this->getEmbedder()->resolveMany($urls);
    }
}
