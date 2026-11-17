---
title: Testing
weight: 40
description: Fake providers' answers and the discovery guard in your application's tests.
---

## Faking providers

Lookups go through Laravel's HTTP client, so `Http::fake()` answers them. Fake the provider's endpoint, not the URL
being embedded:

```php
use Illuminate\Support\Facades\Http;
use Webwizardsusa\OEmbed\Facades\OEmbed;

it('shows the video', function (): void {
    Http::preventStrayRequests();
    Http::fake([
        'www.youtube.com/oembed*' => Http::response([
            'type' => 'video',
            'title' => 'A video',
            'width' => 640,
            'height' => 360,
            'html' => '<iframe src="https://www.youtube.com/embed/abc"></iframe>',
        ]),
    ]);

    expect((string) OEmbed::render('https://youtu.be/abc'))->toContain('https://www.youtube.com/embed/abc');
});
```

A failed lookup is any unsuccessful status: `Http::response([], 404)`.

Answers are stored, failures included, so a test that runs after another with the same URL may read the earlier
answer. Use the `array` cache store in tests (Laravel's default in `phpunit.xml`), which starts empty for each test.

The [form field](../filament/form-field.md#validation) validates with a live lookup, so fake the provider in tests that
submit a form with an `OEmbedInput`.

## Rebinding the URL guard for discovery

[Discovery](../security/discovery.md) checks every host with `Webwizardsusa\OEmbed\UrlGuard`, which looks the host's
addresses up in DNS. In tests, give the container a guard that resolves hosts from a fixed list instead:

```php
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Webwizardsusa\OEmbed\Facades\OEmbed;
use Webwizardsusa\OEmbed\UrlGuard;

it('embeds a page found by discovery', function (): void {
    $this->app->instance(UrlGuard::class, new UrlGuard(
        fn (string $host): array => $host === 'blog.example.com' ? ['93.184.216.34'] : [],
    ));

    Http::preventStrayRequests();
    Http::fake([
        'https://blog.example.com/posts/1' => Http::response(
            '<html><head><link rel="alternate" type="application/json+oembed" href="/oembed?url=x"></head></html>',
            200,
            ['Content-Type' => 'text/html'],
        ),
        'https://blog.example.com/oembed*' => Http::response(
            ['type' => 'rich', 'title' => 'A post', 'html' => '<iframe src="https://blog.example.com/embed/1"></iframe>'],
            200,
            ['Content-Type' => 'application/json'],
        ),
    ]);

    expect(OEmbed::discovery()->resolve('https://blog.example.com/posts/1')?->title())->toBe('A post');
});
```

The closure receives a host name and returns its IPv4 and IPv6 addresses. The guard still applies all its other
rules: a host you map to a private address, such as `10.0.0.8`, is still refused, which is how you test that it is.

The guard is resolved from the container each time discovery runs, so the binding takes effect for every embedder
created afterwards.

> [!CAUTION]
> Only rebind `UrlGuard` in tests. A guard that resolves differently, or a subclass that relaxes `check()`, in
> production would let discovery reach addresses the guard exists to protect, such as `localhost`, your private
> network or the cloud metadata service at `169.254.169.254`. If your production network needs a different policy,
> make it stricter, never looser.

## Faking a store

To test code without touching the cache, pass a store of your own to the embedder with `->store($store)`, or register
one under the default name in the test:

```php
use Webwizardsusa\OEmbed\Facades\OEmbed;

OEmbed::extend('cache', fn () => new InMemoryEmbedStore);
```

`InMemoryEmbedStore` stands for any class implementing `EmbedStore` (see [Custom stores](custom-stores.md)). Register
it before the first lookup in the test, since the manager keeps each store once created.
