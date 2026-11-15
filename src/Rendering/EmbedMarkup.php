<?php

namespace Webwizardsusa\OEmbed\Rendering;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use Webwizardsusa\OEmbed\OEmbedResponse;
use Webwizardsusa\OEmbed\Providers\Provider;
use Webwizardsusa\OEmbed\Support\Url;

/**
 * Builds an embed's display markup from an oEmbed answer, as a render array for the content serializer. The provider's
 * HTML is never passed through: it is parsed, and only the parts each kind of embed needs are rebuilt, with
 * allowlisted attributes. Everything the serializer writes is escaped.
 *
 * - `video` and `rich` answers from a provider with {@see Provider::iframe()} hosts become that provider's `<iframe>`,
 *   whose `src` must be https on one of those hosts.
 * - `rich` answers from a provider with a {@see Provider::script()} become its `<blockquote>`, reduced to text, links and
 *   a few inline tags, followed by the provider's own script.
 * - `photo` answers become an `<img>` linking to the page.
 *
 * Anything else answers null, and the embed is shown as its link.
 */
class EmbedMarkup
{
    /**
     * The iframe `allow` features providers may ask for.
     */
    public const array IFRAME_FEATURES = [
        'accelerometer', 'autoplay', 'clipboard-write', 'encrypted-media', 'fullscreen', 'gyroscope', 'picture-in-picture', 'web-share',
    ];

    /**
     * Tags kept inside a provider's `<blockquote>`; the text of any other element is kept without it.
     */
    protected const array BLOCKQUOTE_TAGS = ['p', 'a', 'br', 'section', 'span', 'strong', 'em', 'b', 'i'];

    /**
     * @param  string  $classPrefix  The player wrapper's class is `{$classPrefix}-frame`.
     */
    public function __construct(
        protected bool $privacyEnhancedYouTube = false,
        protected string $classPrefix = 'oembed',
    ) {}

    /**
     * @return array{kind: string, content: array<mixed>} | null The kind (`video`, `rich` or `photo`) and the elements
     *                                                           that go inside the embed's figure.
     */
    public function build(Provider $provider, OEmbedResponse $response, string $url): ?array
    {
        if ($response->type === 'photo') {
            return $this->photo($response, $url);
        }

        if ($response->html === null || ! in_array($response->type, ['video', 'rich'], true)) {
            return null;
        }

        $document = $this->parse($response->html);

        if ($provider->getIframeHosts() !== []) {
            return $this->iframe($provider, $response, $document);
        }

        if ($provider->getScriptUrl() !== null) {
            return $this->script($provider, $document);
        }

        return null;
    }

    /**
     * @return array{kind: string, content: array<mixed>} | null
     */
    protected function iframe(Provider $provider, OEmbedResponse $response, DOMDocument $document): ?array
    {
        $iframe = $document->getElementsByTagName('iframe')->item(0);

        if (! $iframe instanceof DOMElement) {
            return null;
        }

        $src = $this->iframeSource($provider, trim($iframe->getAttribute('src')));

        if ($src === null) {
            return null;
        }

        $width = $this->size($iframe->getAttribute('width')) ?? $response->width;
        $height = $this->size($iframe->getAttribute('height')) ?? $response->height;
        $features = array_values(array_intersect(
            self::IFRAME_FEATURES,
            array_map(static fn (string $feature): string => strtolower(trim($feature)), explode(';', $iframe->getAttribute('allow'))),
        ));
        $title = trim($iframe->getAttribute('title')) ?: $response->title;

        $attributes = array_filter([
            'src' => $src,
            'width' => $width === null ? null : (string) $width,
            'height' => $height === null ? null : (string) $height,
            'title' => $title,
            'allow' => $features === [] ? null : implode('; ', $features),
            'allowfullscreen' => $iframe->hasAttribute('allowfullscreen') || in_array('fullscreen', $features, true) ? '' : null,
            'loading' => 'lazy',
            'referrerpolicy' => 'strict-origin-when-cross-origin',
            'sandbox' => $provider->sandboxesIframes() ? 'allow-scripts allow-same-origin allow-popups allow-popups-to-escape-sandbox allow-presentation' : null,
        ], static fn (?string $value): bool => $value !== null);

        // A video keeps its shape as it scales, and a portrait one no wider than it is; other players, such as audio,
        // keep their height.
        $style = null;

        if ($response->type === 'video' && $width !== null && $height !== null) {
            $style = "aspect-ratio: {$width} / {$height}".($height > $width ? "; max-width: {$width}px" : '');
        }

        return [
            'kind' => $response->type,
            'content' => [
                ['div', array_filter(['class' => "{$this->classPrefix}-frame", 'style' => $style], static fn (?string $value): bool => $value !== null), ['iframe', $attributes]],
            ],
        ];
    }

    protected function iframeSource(Provider $provider, string $src): ?string
    {
        if (! str_starts_with(strtolower($src), 'https://') || ! Url::isAllowed($src)) {
            return null;
        }

        $host = strtolower((string) parse_url($src, PHP_URL_HOST));

        if (! in_array($host, $provider->getIframeHosts(), true) || parse_url($src, PHP_URL_USER) !== null) {
            return null;
        }

        if ($this->privacyEnhancedYouTube && $host === 'www.youtube.com') {
            return 'https://www.youtube-nocookie.com'.substr($src, strlen('https://www.youtube.com'));
        }

        return $src;
    }

    /**
     * @return array{kind: string, content: array<mixed>} | null
     */
    protected function script(Provider $provider, DOMDocument $document): ?array
    {
        $blockquote = $document->getElementsByTagName('blockquote')->item(0);

        if (! $blockquote instanceof DOMElement) {
            return null;
        }

        return [
            'kind' => 'rich',
            'content' => [
                ['blockquote', $this->blockquoteAttributes($blockquote), ...$this->inlineChildren($blockquote)],
                ['script', ['async' => '', 'src' => $provider->getScriptUrl(), 'charset' => 'utf-8']],
            ],
        ];
    }

    /**
     * The provider's script finds its blockquote by class and data attributes, so those are kept: class names that
     * are plain tokens, `data-*` values (inert text), and a `cite` that is http(s).
     *
     * @return array<string, string>
     */
    protected function blockquoteAttributes(DOMElement $blockquote): array
    {
        $attributes = [];

        $classes = array_filter(
            preg_split('/\s+/', $blockquote->getAttribute('class')) ?: [],
            static fn (string $class): bool => preg_match('/^[A-Za-z0-9_-]+$/', $class) === 1,
        );

        if ($classes !== []) {
            $attributes['class'] = implode(' ', $classes);
        }

        if (Url::isAllowed($blockquote->getAttribute('cite'))) {
            $attributes['cite'] = trim($blockquote->getAttribute('cite'));
        }

        foreach ($blockquote->attributes ?? [] as $attribute) {
            if (preg_match('/^data-[a-z0-9-]+$/', $attribute->name) === 1) {
                $attributes[$attribute->name] = $attribute->value;
            }
        }

        return $attributes;
    }

    /**
     * @return array<mixed>
     */
    protected function inlineChildren(DOMNode $parent): array
    {
        $children = [];

        foreach ($parent->childNodes as $child) {
            if ($child instanceof DOMText) {
                $children[] = $child->data;

                continue;
            }

            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, ['script', 'style', 'template'], true)) {
                continue;
            }

            if (! in_array($tag, self::BLOCKQUOTE_TAGS, true)) {
                $children = [...$children, ...$this->inlineChildren($child)];

                continue;
            }

            $attributes = [];

            if ($tag === 'a' && Url::isAllowed($child->getAttribute('href'))) {
                $attributes['href'] = trim($child->getAttribute('href'));
            }

            if ($tag === 'p') {
                $attributes = array_filter([
                    'lang' => preg_match('/^[A-Za-z0-9-]{1,35}$/', $child->getAttribute('lang')) === 1 ? $child->getAttribute('lang') : null,
                    'dir' => in_array($child->getAttribute('dir'), ['ltr', 'rtl', 'auto'], true) ? $child->getAttribute('dir') : null,
                ], static fn (?string $value): bool => $value !== null);
            }

            $children[] = [$tag, $attributes, ...$this->inlineChildren($child)];
        }

        return $children;
    }

    /**
     * @return array{kind: string, content: array<mixed>}
     */
    protected function photo(OEmbedResponse $response, string $url): array
    {
        $image = array_filter([
            'src' => $response->url,
            'alt' => $response->title ?? '',
            'width' => $response->width === null ? null : (string) $response->width,
            'height' => $response->height === null ? null : (string) $response->height,
            'loading' => 'lazy',
        ], static fn (?string $value): bool => $value !== null);

        return ['kind' => 'photo', 'content' => [['a', ['href' => $url], ['img', $image]]]];
    }

    protected function parse(string $html): DOMDocument
    {
        $document = new DOMDocument;
        $document->loadHTML('<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>'.$html.'</body></html>', LIBXML_NOERROR | LIBXML_NONET);

        return $document;
    }

    protected function size(string $value): ?int
    {
        $size = filter_var(trim($value), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 10_000]]);

        return $size === false ? null : $size;
    }
}
