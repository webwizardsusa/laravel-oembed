<?php

use Webwizardsusa\OEmbed\Providers\BuiltInProviders;
use Webwizardsusa\OEmbed\Providers\Provider;
use Webwizardsusa\OEmbed\Providers\ProviderRegistry;
use Webwizardsusa\OEmbed\Tests\TestCase;

uses(TestCase::class);

function builtInProviderFor(mixed $url): ?string
{
    return ProviderRegistry::make(BuiltInProviders::all())->match($url)?->getName();
}

it('matches each built-in provider\'s URLs', function (string $url, string $provider): void {
    expect(builtInProviderFor($url))->toBe($provider);
})->with([
    ['https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'youtube'],
    ['https://youtube.com/shorts/abc123', 'youtube'],
    ['https://m.youtube.com/watch?v=abc', 'youtube'],
    ['https://youtu.be/dQw4w9WgXcQ', 'youtube'],
    ['https://vimeo.com/76979871', 'vimeo'],
    ['https://open.spotify.com/track/4uLU6hMCjMI75M1A2tKUQC', 'spotify'],
    ['https://soundcloud.com/artist/track', 'soundcloud'],
    ['https://twitter.com/jack/status/20', 'x'],
    ['https://x.com/jack/status/20', 'x'],
    ['https://www.tiktok.com/@user/video/123', 'tiktok'],
    ['https://bsky.app/profile/someone.bsky.social/post/3k', 'bluesky'],
    ['https://www.flickr.com/photos/someone/123', 'flickr'],
    ['HTTPS://WWW.YOUTUBE.COM/watch?v=abc', 'youtube'],
]);

it('does not match lookalike hosts, other paths or unsafe URLs', function (mixed $url): void {
    expect(builtInProviderFor($url))->toBeNull();
})->with([
    'another domain ending in the name' => 'https://evilyoutube.com/watch?v=abc',
    'the name as a subdomain' => 'https://youtube.com.evil.example/watch?v=abc',
    'credentials before the host' => 'https://youtube.com@evil.example/watch?v=abc',
    'a path the provider does not embed' => 'https://www.youtube.com/feed/trending',
    'an X profile rather than a post' => 'https://x.com/jack',
    'a javascript URL' => 'javascript://youtube.com/watch',
    'a relative URL' => '/watch?v=abc',
    'a URL that is too long' => 'https://youtu.be/'.str_repeat('a', ProviderRegistry::MAX_URL_LENGTH),
    'not a string' => null,
]);

it('replaces a provider added under the same name and removes providers by name', function (): void {
    $registry = ProviderRegistry::make(BuiltInProviders::all())
        ->add([Provider::make('youtube')->urls(['videos.example.com/*'])->endpoint('https://videos.example.com/oembed')])
        ->remove(['vimeo']);

    expect($registry->match('https://videos.example.com/1')?->getName())->toBe('youtube')
        ->and($registry->match('https://youtu.be/abc'))->toBeNull()
        ->and($registry->match('https://vimeo.com/1'))->toBeNull()
        ->and($registry->match('https://open.spotify.com/track/1')?->getName())->toBe('spotify');
});

it('refuses a provider that is misconfigured', function (Closure $configure): void {
    expect($configure)->toThrow(InvalidArgumentException::class);
})->with([
    'a name that is not a slug' => [fn () => Provider::make('You Tube')],
    'a wildcard inside a host' => [fn () => Provider::make('a')->urls(['you*.com/*'])],
    'a wildcard without a dot' => [fn () => Provider::make('a')->urls(['*youtube.com/*'])],
    'an http endpoint' => [fn () => Provider::make('a')->endpoint('http://example.com/oembed')],
    'an endpoint without a host' => [fn () => Provider::make('a')->endpoint('https://')],
    'an http script' => [fn () => Provider::make('a')->script('http://example.com/embed.js')],
]);
