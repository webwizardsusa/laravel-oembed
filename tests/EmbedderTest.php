<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Http;
use Webwizardsusa\OEmbed\Embedder;
use Webwizardsusa\OEmbed\Facades\OEmbed;
use Webwizardsusa\OEmbed\Rendering\HtmlBuilder;
use Webwizardsusa\OEmbed\Tests\TestCase;
use Webwizardsusa\OEmbed\UrlGuard;

uses(TestCase::class);

/**
 * @param  array<string, mixed>  $answer
 */
function fakeYouTubeAnswer(array $answer): void
{
    Http::preventStrayRequests();
    Http::fake(['www.youtube.com/oembed*' => Http::response($answer)]);
}

function videoAnswerWith(string $html): array
{
    return ['type' => 'video', 'title' => 'A video', 'width' => 640, 'height' => 360, 'html' => $html];
}

it('renders a video as the provider\'s player, rebuilt with only the attributes it allows, and the caption', function (): void {
    fakeYouTubeAnswer(videoAnswerWith('<iframe src="https://www.youtube.com/embed/abc?feature=oembed" width="640" height="360" onload="steal()" style="position:fixed" allow="autoplay; camera; encrypted-media" allowfullscreen></iframe>'));

    expect(Embedder::make()->render('https://youtu.be/abc', 'Watch <this>')->toHtml())->toBe(
        '<figure class="oembed" data-kind="video"><div class="oembed-frame" style="aspect-ratio: 640 / 360">'
        .'<iframe src="https://www.youtube.com/embed/abc?feature=oembed" width="640" height="360" title="A video" allow="autoplay; encrypted-media" allowfullscreen="" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>'
        .'</div><figcaption>Watch &lt;this&gt;</figcaption></figure>',
    );
});

it('plays YouTube from the privacy-enhanced host when asked to', function (): void {
    fakeYouTubeAnswer(videoAnswerWith('<iframe src="https://www.youtube.com/embed/abc"></iframe>'));

    expect(Embedder::make()->privacyEnhancedYouTube()->render('https://youtu.be/abc')->toHtml())
        ->toContain('src="https://www.youtube-nocookie.com/embed/abc"');
});

it('keeps a portrait video no wider than it is', function (): void {
    fakeYouTubeAnswer(videoAnswerWith('<iframe src="https://www.youtube.com/embed/abc" width="325" height="578"></iframe>'));

    expect(Embedder::make()->render('https://youtu.be/abc')->toHtml())->toContain('style="aspect-ratio: 325 / 578; max-width: 325px"');
});

it('renders the link instead of a player the provider may not load', function (string $html): void {
    fakeYouTubeAnswer(videoAnswerWith($html));

    expect(Embedder::make()->render('https://youtu.be/abc')->toHtml())
        ->toBe('<figure class="oembed"><a href="https://youtu.be/abc">A video</a></figure>');
})->with([
    'another host' => '<iframe src="https://evil.example/embed/abc"></iframe>',
    'a host that only starts with the provider\'s' => '<iframe src="https://www.youtube.com.evil.example/embed/abc"></iframe>',
    'credentials before the host' => '<iframe src="https://www.youtube.com@evil.example/embed/abc"></iframe>',
    'http' => '<iframe src="http://www.youtube.com/embed/abc"></iframe>',
    'a javascript URL' => '<iframe src="javascript:alert(1)"></iframe>',
    'a script instead of an iframe' => '<script src="https://www.youtube.com/x.js"></script>',
]);

it('renders a post as the provider\'s blockquote and its own script, never markup or scripts from the answer', function (): void {
    Http::preventStrayRequests();
    Http::fake(['publish.x.com/oembed*' => Http::response([
        'type' => 'rich',
        'html' => '<blockquote class="twitter-tweet" data-width="550" onclick="steal()"><p lang="en" dir="ltr" onmouseover="steal()">Hello <img src="x" onerror="steal()">world <a href="javascript:steal()">bad</a> <a href="https://x.com/jack/status/20">good</a></p><script>steal()</script></blockquote><script async src="https://evil.example/widgets.js"></script>',
    ])]);

    expect(Embedder::make()->render('https://x.com/jack/status/20')->toHtml())->toBe(
        '<figure class="oembed" data-kind="rich">'
        .'<blockquote class="twitter-tweet" data-width="550"><p lang="en" dir="ltr">Hello world <a>bad</a> <a href="https://x.com/jack/status/20">good</a></p></blockquote>'
        .'<script async="" src="https://platform.x.com/widgets.js" charset="utf-8"></script></figure>',
    );
});

it('renders a photo linked to its page', function (): void {
    Http::preventStrayRequests();
    Http::fake(['www.flickr.com/services/oembed/*' => Http::response([
        'type' => 'photo', 'title' => 'Bees "&" <b>', 'url' => 'https://live.staticflickr.com/1.jpg', 'width' => 800, 'height' => 533,
    ])]);

    expect(Embedder::make()->render('https://www.flickr.com/photos/bees/1')->toHtml())->toBe(
        '<figure class="oembed" data-kind="photo"><a href="https://www.flickr.com/photos/bees/1"><img src="https://live.staticflickr.com/1.jpg" alt="Bees &quot;&amp;&quot; &lt;b&gt;" width="800" height="533" loading="lazy"></a></figure>',
    );
});

it('renders the link for a URL with nothing to embed, and nothing for a URL that is not http(s)', function (): void {
    Http::preventStrayRequests();
    Http::fake(['www.youtube.com/oembed*' => Http::response([], 404)]);

    expect(Embedder::make()->render('https://youtu.be/abc')->toHtml())->toBe('<figure class="oembed"><a href="https://youtu.be/abc">https://youtu.be/abc</a></figure>')
        ->and(Embedder::make()->render('https://example.com/page')->toHtml())->toBe('<figure class="oembed"><a href="https://example.com/page">https://example.com/page</a></figure>')
        ->and(Embedder::make()->render('javascript:alert(1)')->toHtml())->toBe('')
        ->and(Embedder::make()->render(null)->toHtml())->toBe('');
});

it('renders a discovered player from the page\'s own host, sandboxed', function (): void {
    $this->app->instance(UrlGuard::class, new UrlGuard(static fn (string $host): array => $host === 'blog.example.com' ? ['93.184.216.34'] : []));
    Http::preventStrayRequests();
    Http::fake([
        'https://blog.example.com/oembed*' => Http::response(['type' => 'rich', 'title' => 'A post', 'html' => '<iframe src="https://blog.example.com/embed/1"></iframe>'], 200, ['Content-Type' => 'application/json']),
        'https://blog.example.com/posts/1' => Http::response('<html><head><link rel="alternate" type="application/json+oembed" href="/oembed?url=x"></head></html>', 200, ['Content-Type' => 'text/html']),
    ]);

    expect(Embedder::make()->render('https://blog.example.com/posts/1')->toHtml())->toBe('<figure class="oembed"><a href="https://blog.example.com/posts/1">https://blog.example.com/posts/1</a></figure>')
        ->and(Embedder::make()->discovery()->render('https://blog.example.com/posts/1')->toHtml())
        ->toContain('<iframe src="https://blog.example.com/embed/1"')
        ->toContain('sandbox="allow-scripts allow-same-origin allow-popups allow-popups-to-escape-sandbox allow-presentation"');
});

it('looks a batch of URLs up at once and answers each one\'s embed', function (): void {
    Http::preventStrayRequests();
    Http::fake([
        'www.youtube.com/oembed*' => fn (Request $request) => Http::response(['type' => 'video', 'title' => "Video {$request['url']}", 'thumbnail_url' => 'https://i.ytimg.com/1.jpg', 'html' => '<iframe></iframe>']),
        'vimeo.com/api/oembed.json*' => Http::response([], 404),
    ]);

    $embeds = Embedder::make()->resolveMany([' https://youtu.be/a ', 'https://youtu.be/b', 'https://vimeo.com/1', 'https://example.com/page', 'javascript:alert(1)', null]);

    expect(array_map(static fn ($embed): ?array => $embed?->toArray(), $embeds))->toBe([
        'https://youtu.be/a' => ['url' => 'https://youtu.be/a', 'provider' => 'youtube', 'title' => 'Video https://youtu.be/a', 'thumbnail' => 'https://i.ytimg.com/1.jpg'],
        'https://youtu.be/b' => ['url' => 'https://youtu.be/b', 'provider' => 'youtube', 'title' => 'Video https://youtu.be/b', 'thumbnail' => 'https://i.ytimg.com/1.jpg'],
        'https://vimeo.com/1' => null,
    ]);

    Http::assertSentCount(3);
});

it('takes its defaults from the config', function (): void {
    config(['oembed.discovery' => true, 'oembed.privacy_enhanced_youtube' => true, 'oembed.max_width' => 640]);

    $embedder = Embedder::make();

    expect($embedder->discovers())->toBeTrue()
        ->and($embedder->isPrivacyEnhancedYouTube())->toBeTrue()
        ->and($embedder->getMaxWidth())->toBe(640)
        ->and($embedder->takes('https://example.com/page'))->toBeTrue();
});

it('gives a new embedder each time through the facade, so one call\'s settings never reach the next', function (): void {
    expect(OEmbed::discovery()->discovers())->toBeTrue()
        ->and(OEmbed::takes('https://example.com/page'))->toBeFalse();
});

it('renders the embed for a URL with the Blade component', function (): void {
    fakeYouTubeAnswer(videoAnswerWith('<iframe src="https://www.youtube.com/embed/abc"></iframe>'));

    expect(Blade::render('<x-oembed :url="$url" caption="A <caption>" />', ['url' => 'https://youtu.be/abc']))
        ->toContain('<iframe src="https://www.youtube.com/embed/abc"')
        ->toContain('<figcaption>A &lt;caption&gt;</figcaption>');
});

it('writes markup arrays as escaped HTML, leaving out event handlers and odd attribute names', function (): void {
    expect(HtmlBuilder::build(['p', ['class' => 'a "b"', 'onclick' => 'x()', 'bad name' => 'y', 'hidden' => null, 'data-x' => ''], 'Tom & <Jerry>', ['br'], ['img', ['src' => 'https://e.com/1.png']]]))
        ->toBe('<p class="a &quot;b&quot;" data-x="">Tom &amp; &lt;Jerry&gt;<br><img src="https://e.com/1.png"></p>')
        ->and(HtmlBuilder::build(['script src="x"', 'alert(1)']))->toBe('');
});

it('provides the stylesheet rendered embeds need', function (): void {
    expect(Embedder::make()->stylesheet())->toContain('.oembed-frame iframe')->toContain('.oembed figcaption');
});
