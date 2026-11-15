<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Webwizardsusa\OEmbed\OEmbedClient;
use Webwizardsusa\OEmbed\OEmbedResponse;
use Webwizardsusa\OEmbed\Providers\Provider;
use Webwizardsusa\OEmbed\Tests\TestCase;

uses(TestCase::class);

function videoProvider(): Provider
{
    return Provider::make('videos')
        ->urls(['videos.example.com/*'])
        ->endpoint('https://videos.example.com/oembed', ['key' => 'secret']);
}

/**
 * @return array<string, mixed>
 */
function videoAnswer(array $overrides = []): array
{
    return [
        'type' => 'video',
        'version' => '1.0',
        'title' => 'A video',
        'provider_name' => 'Videos',
        'thumbnail_url' => 'https://videos.example.com/1.jpg',
        'width' => 640,
        'height' => '360',
        'html' => '<iframe src="https://videos.example.com/embed/1"></iframe>',
        ...$overrides,
    ];
}

it('asks the provider\'s endpoint about the URL and keeps the usable parts of the answer', function (): void {
    Http::preventStrayRequests();
    Http::fake(['videos.example.com/oembed*' => Http::response(videoAnswer())]);

    $response = (new OEmbedClient)->fetch(videoProvider(), 'https://videos.example.com/1', 800);

    expect($response)->toEqual(new OEmbedResponse(
        type: 'video',
        title: 'A video',
        providerName: 'Videos',
        thumbnailUrl: 'https://videos.example.com/1.jpg',
        width: 640,
        height: 360,
        html: '<iframe src="https://videos.example.com/embed/1"></iframe>',
    ));

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://videos.example.com/oembed?key=secret&url=https%3A%2F%2Fvideos.example.com%2F1&format=json&maxwidth=800');
});

it('answers null when the lookup fails', function (Closure $response): void {
    Http::preventStrayRequests();
    Http::fake(['videos.example.com/oembed*' => $response()]);

    expect((new OEmbedClient)->fetch(videoProvider(), 'https://videos.example.com/1'))->toBeNull();
})->with([
    'not found' => [fn () => Http::response(['error' => 'gone'], 404)],
    'a connection failure' => [fn () => Http::failedConnection()],
    'a redirect, which is not followed' => [fn () => Http::response('', 302, ['Location' => 'http://169.254.169.254/latest'])],
    'not JSON' => [fn () => Http::response('<html></html>', 200)],
    'an unknown type' => [fn () => Http::response(videoAnswer(['type' => 'script']))],
    'a video without markup' => [fn () => Http::response(videoAnswer(['html' => null]))],
    'an answer that is too large' => [fn () => Http::response(videoAnswer(['title' => str_repeat('a', OEmbedClient::MAX_RESPONSE_BYTES)]))],
]);

it('drops parts of an answer that are not safe or not the right shape', function (): void {
    $response = OEmbedResponse::fromArray(videoAnswer([
        'thumbnail_url' => 'javascript:alert(1)',
        'author_url' => 'data:text/html,hi',
        'width' => -5,
        'height' => 'tall',
        'title' => ['not', 'text'],
    ]));

    expect($response)->not->toBeNull()
        ->and($response->thumbnailUrl)->toBeNull()
        ->and($response->authorUrl)->toBeNull()
        ->and($response->width)->toBeNull()
        ->and($response->height)->toBeNull()
        ->and($response->title)->toBeNull();
});

it('keeps a photo\'s URL and no markup', function (): void {
    $response = OEmbedResponse::fromArray(['type' => 'photo', 'url' => 'https://photos.example.com/1.jpg', 'html' => '<script></script>']);

    expect($response->url)->toBe('https://photos.example.com/1.jpg')
        ->and($response->html)->toBeNull()
        ->and(OEmbedResponse::fromArray(['type' => 'photo', 'url' => 'ftp://photos.example.com/1.jpg']))->toBeNull();
});

it('sends no request for a provider without an endpoint', function (): void {
    Http::preventStrayRequests();
    Http::fake();

    expect((new OEmbedClient)->fetch(Provider::make('none')->urls(['none.example.com/*']), 'https://none.example.com/1'))->toBeNull();

    Http::assertNothingSent();
});
