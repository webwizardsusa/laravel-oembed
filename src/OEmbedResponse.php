<?php

namespace Webwizardsusa\OEmbed;

use Webwizardsusa\OEmbed\Support\Url;

/**
 * The parts of an oEmbed answer the plugin uses, each checked: text is trimmed and capped, URLs are absolute http(s),
 * sizes are positive integers. `html` is kept as the provider sent it and must only be read through the renderer,
 * which rebuilds the markup it allows.
 */
final readonly class OEmbedResponse
{
    public const array TYPES = ['photo', 'video', 'rich', 'link'];

    protected const int MAX_TEXT_LENGTH = 500;

    protected const int MAX_HTML_LENGTH = 100_000;

    public function __construct(
        public string $type,
        public ?string $title = null,
        public ?string $authorName = null,
        public ?string $authorUrl = null,
        public ?string $providerName = null,
        public ?string $thumbnailUrl = null,
        public ?int $thumbnailWidth = null,
        public ?int $thumbnailHeight = null,
        public ?int $width = null,
        public ?int $height = null,
        public ?string $html = null,
        public ?string $url = null,
    ) {}

    /**
     * The answer as a provider sent it, or as {@see toArray()} wrote it. Null when it isn't a usable oEmbed answer.
     *
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): ?self
    {
        $type = $data['type'] ?? null;

        if (! in_array($type, self::TYPES, true)) {
            return null;
        }

        $html = is_string($data['html'] ?? null) && strlen($data['html']) <= self::MAX_HTML_LENGTH ? $data['html'] : null;
        $url = self::url($data['url'] ?? null);

        // A photo is its URL, and a video or rich answer is its markup; without them there's nothing to embed.
        if (($type === 'photo' && $url === null) || (in_array($type, ['video', 'rich'], true) && $html === null)) {
            return null;
        }

        return new self(
            type: $type,
            title: self::text($data['title'] ?? null),
            authorName: self::text($data['author_name'] ?? null),
            authorUrl: self::url($data['author_url'] ?? null),
            providerName: self::text($data['provider_name'] ?? null),
            thumbnailUrl: self::url($data['thumbnail_url'] ?? null),
            thumbnailWidth: self::size($data['thumbnail_width'] ?? null),
            thumbnailHeight: self::size($data['thumbnail_height'] ?? null),
            width: self::size($data['width'] ?? null),
            height: self::size($data['height'] ?? null),
            html: $type === 'photo' || $type === 'link' ? null : $html,
            url: $type === 'photo' ? $url : null,
        );
    }

    /**
     * @return array<string, string|int|null> In oEmbed's own key names, so {@see fromArray()} reads it back.
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'author_name' => $this->authorName,
            'author_url' => $this->authorUrl,
            'provider_name' => $this->providerName,
            'thumbnail_url' => $this->thumbnailUrl,
            'thumbnail_width' => $this->thumbnailWidth,
            'thumbnail_height' => $this->thumbnailHeight,
            'width' => $this->width,
            'height' => $this->height,
            'html' => $this->html,
            'url' => $this->url,
        ];
    }

    protected static function text(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return mb_substr(trim($value), 0, self::MAX_TEXT_LENGTH);
    }

    protected static function url(mixed $value): ?string
    {
        return Url::isAllowed($value) && strlen($value) <= 2048 ? trim($value) : null;
    }

    /**
     * Providers send sizes as numbers or numeric strings.
     */
    protected static function size(mixed $value): ?int
    {
        $size = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 10_000]]);

        return $size === false ? null : $size;
    }
}
