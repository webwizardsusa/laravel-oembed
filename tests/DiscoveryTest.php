<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Webwizardsusa\OEmbed\Discovery;
use Webwizardsusa\OEmbed\Tests\TestCase;
use Webwizardsusa\OEmbed\UrlGuard;

uses(TestCase::class);

function discovery(): Discovery
{
    return new Discovery(new UrlGuard(static fn (string $host): array => match ($host) {
        'blog.example.com', 'cdn.example.com' => ['93.184.216.34'],
        'internal.example.com' => ['10.0.0.8'],
        default => [],
    }));
}

function pageLinking(string $href): string
{
    return '<!DOCTYPE html><html><head><title>A post</title>'
        .'<link rel="alternate" type="application/json+oembed" href="'.$href.'">'
        .'</head><body><p>Hello</p></body></html>';
}

function htmlResponse(string $html): mixed
{
    return Http::response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
}

function oembedResponse(): mixed
{
    return Http::response(['type' => 'rich', 'title' => 'A post', 'html' => '<iframe src="https://blog.example.com/embed/1"></iframe>'], 200, ['Content-Type' => 'application/json']);
}

it('finds the oEmbed endpoint a page names and asks it about the page', function (): void {
    Http::preventStrayRequests();
    Http::fake([
        'https://blog.example.com/posts/1' => htmlResponse(pageLinking('/oembed?url=https%3A%2F%2Fblog.example.com%2Fposts%2F1&amp;format=json')),
        'https://blog.example.com/oembed*' => oembedResponse(),
    ]);

    expect(discovery()->discover('https://blog.example.com/posts/1', 640)?->title)->toBe('A post');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://blog.example.com/oembed?url=https%3A%2F%2Fblog.example.com%2Fposts%2F1&format=json&maxwidth=640');
});

it('follows a redirect to a public address', function (): void {
    Http::preventStrayRequests();
    Http::fake([
        'http://blog.example.com/posts/1' => Http::response('', 301, ['Location' => 'https://blog.example.com/posts/1']),
        'https://blog.example.com/posts/1' => htmlResponse(pageLinking('https://cdn.example.com/oembed?url=x')),
        'https://cdn.example.com/oembed*' => oembedResponse(),
    ]);

    expect(discovery()->discover('http://blog.example.com/posts/1'))->not->toBeNull();
});

it('never requests a private address, whether the page, a redirect or the endpoint names it', function (Closure $responses, string $url): void {
    Http::preventStrayRequests();
    // The private addresses answer too, so a request to one would be recorded rather than refused as stray.
    Http::fake([
        ...$responses(),
        'http://169.254.169.254/*' => htmlResponse(pageLinking('https://blog.example.com/oembed')),
        'https://internal.example.com/*' => htmlResponse(pageLinking('https://blog.example.com/oembed')),
        'http://127.0.0.1:8080/*' => oembedResponse(),
        'https://blog.example.com/oembed*' => oembedResponse(),
    ]);

    expect(discovery()->discover($url))->toBeNull();

    Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '169.254.169.254')
        || str_contains($request->url(), 'internal.example.com')
        || str_contains($request->url(), '127.0.0.1'));
})->with([
    'the page' => [fn () => [], 'http://169.254.169.254/latest/meta-data/'],
    'a page on a name resolving to a private address' => [fn () => [], 'https://internal.example.com/posts/1'],
    'a redirect' => [fn () => [
        'https://blog.example.com/posts/1' => Http::response('', 302, ['Location' => 'http://169.254.169.254/latest/meta-data/']),
    ], 'https://blog.example.com/posts/1'],
    'the endpoint' => [fn () => [
        'https://blog.example.com/posts/1' => htmlResponse(pageLinking('http://127.0.0.1:8080/oembed')),
    ], 'https://blog.example.com/posts/1'],
    'an endpoint on a name resolving to a private address' => [fn () => [
        'https://blog.example.com/posts/1' => htmlResponse(pageLinking('https://internal.example.com/oembed')),
    ], 'https://blog.example.com/posts/1'],
]);

it('answers null for a page it cannot discover anything on', function (Closure $page): void {
    Http::preventStrayRequests();
    Http::fake([
        'https://blog.example.com/posts/1' => $page(),
        'https://blog.example.com/oembed*' => oembedResponse(),
    ]);

    expect(discovery()->discover('https://blog.example.com/posts/1'))->toBeNull();
})->with([
    'no oEmbed link' => [fn () => htmlResponse('<html><head><title>Plain</title></head></html>')],
    'an XML oEmbed link only' => [fn () => htmlResponse('<html><head><link rel="alternate" type="text/xml+oembed" href="/oembed"></head></html>')],
    'a javascript endpoint' => [fn () => htmlResponse(pageLinking('javascript:alert(1)'))],
    'not HTML' => [fn () => Http::response(pageLinking('/oembed'), 200, ['Content-Type' => 'application/pdf'])],
    'an error page' => [fn () => Http::response(pageLinking('/oembed'), 404, ['Content-Type' => 'text/html'])],
    'a page that is too large' => [fn () => htmlResponse(pageLinking('/oembed').str_repeat('a', Discovery::MAX_PAGE_BYTES + 10_000))],
]);

it('stops following redirects after a few', function (): void {
    Http::preventStrayRequests();
    Http::fake(['https://blog.example.com/*' => fn (Request $request) => Http::response('', 302, ['Location' => $request->url().'x'])]);

    expect(discovery()->discover('https://blog.example.com/a'))->toBeNull();

    Http::assertSentCount(Discovery::MAX_REDIRECTS + 1);
});
