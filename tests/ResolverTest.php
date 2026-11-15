<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Webwizardsusa\OEmbed\EmbedCache;
use Webwizardsusa\OEmbed\Embedder;
use Webwizardsusa\OEmbed\Resolver;
use Webwizardsusa\OEmbed\Tests\TestCase;

uses(TestCase::class);

function embedResolver(): Resolver
{
    return Embedder::make()->getResolver();
}

function youtubeAnswer(string $title): array
{
    return ['type' => 'video', 'title' => $title, 'html' => '<iframe src="https://www.youtube.com/embed/x"></iframe>'];
}

it('fetches every uncached URL in one pool, and answers from the cache after that', function (): void {
    Http::preventStrayRequests();
    Http::fake([
        'www.youtube.com/oembed*' => fn (Request $request) => Http::response(youtubeAnswer($request['url'])),
        'vimeo.com/api/oembed.json*' => Http::response(['type' => 'video', 'title' => 'Vimeo', 'html' => '<iframe></iframe>']),
    ]);

    $urls = ['https://youtu.be/a', 'https://youtu.be/b', 'https://vimeo.com/1', 'https://youtu.be/a'];

    $first = embedResolver()->resolveMany($urls);
    $second = embedResolver()->resolveMany($urls);

    expect(array_map(fn ($response) => $response?->title, $first))->toBe([
        'https://youtu.be/a' => 'https://youtu.be/a',
        'https://youtu.be/b' => 'https://youtu.be/b',
        'https://vimeo.com/1' => 'Vimeo',
    ])->and($second)->toEqual($first);

    Http::assertSentCount(3);
});

it('remembers a failed lookup for the failure time only', function (): void {
    Http::preventStrayRequests();
    Http::fake(['www.youtube.com/oembed*' => Http::sequence()->push([], 404)->push(youtubeAnswer('Back'))]);
    config(['oembed.cache.failure_ttl' => 60]);

    expect(embedResolver()->resolve('https://youtu.be/a'))->toBeNull()
        ->and(embedResolver()->resolve('https://youtu.be/a'))->toBeNull();

    $this->travel(61)->seconds();

    expect(embedResolver()->resolve('https://youtu.be/a')?->title)->toBe('Back');

    Http::assertSentCount(2);
});

it('sends nothing for a URL no provider takes', function (): void {
    Http::preventStrayRequests();
    Http::fake();

    expect(embedResolver()->resolveMany(['https://example.com/page']))->toBe(['https://example.com/page' => null]);

    Http::assertNothingSent();
});

it('caches answers separately for each maximum width', function (): void {
    Http::preventStrayRequests();
    Http::fake(['www.youtube.com/oembed*' => fn (Request $request) => Http::response(youtubeAnswer("width {$request['maxwidth']}"))]);

    expect(Embedder::make()->maxWidth(400)->getResolver()->resolve('https://youtu.be/a')?->title)->toBe('width 400')
        ->and(Embedder::make()->maxWidth(600)->getResolver()->resolve('https://youtu.be/a')?->title)->toBe('width 600');
});

it('uses the configured cache store', function (): void {
    Http::preventStrayRequests();
    Http::fake(['www.youtube.com/oembed*' => Http::response(youtubeAnswer('Stored'))]);
    config(['cache.stores.embeds' => ['driver' => 'array'], 'oembed.cache.store' => 'embeds']);

    embedResolver()->resolve('https://youtu.be/a');

    $key = EmbedCache::key('youtube', 'https://youtu.be/a', 800);

    expect(Cache::store('embeds')->get($key))->toBeArray()
        ->and(Cache::store()->has($key))->toBeFalse();
});
