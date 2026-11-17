<?php

namespace Webwizardsusa\OEmbed\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Text;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Webwizardsusa\OEmbed\Embed;
use Webwizardsusa\OEmbed\Filament\Concerns\HasEmbedder;
use Webwizardsusa\OEmbed\Rendering\HtmlBuilder;
use Webwizardsusa\OEmbed\Support\Url;

/**
 * A URL field for something to embed. It stores the URL as a string, refuses URLs that can't be embedded, and shows
 * a preview under the field once the URL is entered: a card with the thumbnail and title (the default), the embed
 * itself (`->preview('embed')`; posts that need their provider's script, such as X and Bluesky, still show their card,
 * because third-party scripts are not run in the panel), or nothing (`->preview(false)`). The preview uses `belowContent()`, so don't set that
 * yourself; `helperText()` still works.
 */
class OEmbedInput extends TextInput
{
    use HasEmbedder;

    protected string|false $preview = 'card';

    protected string|Htmlable|Closure|null $helperText = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->url();
        $this->maxLength(2048);
        $this->live(onBlur: true);

        $this->rule(static fn (OEmbedInput $component): Closure => static function (string $attribute, mixed $value, Closure $fail) use ($component): void {
            if (blank($value)) {
                return;
            }

            if (! $component->getEmbedder()->takes($value)) {
                $fail(__('oembed::oembed.validation.unsupported'));

                return;
            }

            if ($component->getEmbedder()->resolve($value) === null) {
                $fail(__('oembed::oembed.validation.unavailable'));
            }
        });

        // The helper text and the preview share the space below the field.
        $this->belowContent(static fn (OEmbedInput $component, mixed $state): array => array_values(array_filter([
            filled($helperText = $component->evaluate($component->helperText)) ? Text::make($helperText) : null,
            $component->getPreviewHtml($state),
        ])));
    }

    /**
     * Shown above the preview. (Filament's own `helperText()` would take the space the preview uses.)
     */
    public function helperText(string|Htmlable|Closure|null $text): static
    {
        $this->helperText = $text;

        return $this;
    }

    /**
     * `'card'` (the default), `'embed'`, or `false` for no preview. With `'embed'`, a post that needs its provider's
     * script, or an embed that would only show its link, is previewed as its card.
     */
    public function preview(string|false $preview): static
    {
        $this->preview = in_array($preview, ['card', 'embed'], true) ? $preview : false;

        return $this;
    }

    public function getPreview(): string|false
    {
        return $this->preview;
    }

    public function getPreviewHtml(mixed $state): ?Htmlable
    {
        if ($this->preview === false || ! is_string($state) || ! Url::isAllowed($state) || ! $this->getEmbedder()->takes($state)) {
            return null;
        }

        $embed = $this->getEmbedder()->resolve($state);

        if ($embed === null) {
            return null;
        }

        if ($this->preview === 'embed') {
            $html = $embed->toHtml();

            // Posts that need their provider's script (X, Bluesky) and embeds that would only show their link are
            // previewed as their card: third-party scripts are not run in the panel.
            if (str_contains($html, 'data-kind="') && ! str_contains($html, '<script')) {
                return new HtmlString('<div class="oembed-preview">'.$html.'</div>');
            }
        }

        return new HtmlString(static::card($embed));
    }

    protected static function card(Embed $embed): string
    {
        $body = array_filter([
            filled($embed->providerLabel()) ? ['span', ['class' => 'oembed-card-provider'], $embed->providerLabel()] : null,
            ['span', ['class' => 'oembed-card-title'], $embed->title() ?? $embed->url],
            ['span', ['class' => 'oembed-card-url'], $embed->url],
        ]);

        return HtmlBuilder::build([
            'div',
            ['class' => 'oembed-card'],
            ...(Url::isAllowed($embed->thumbnail()) ? [['img', ['src' => $embed->thumbnail(), 'alt' => '', 'loading' => 'lazy', 'referrerpolicy' => 'no-referrer']]] : []),
            ['div', ['class' => 'oembed-card-body'], ...$body],
        ]);
    }
}
