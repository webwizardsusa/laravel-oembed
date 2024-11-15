<?php
beforeEach(function () {
    $this->fakeResponses();
});
it('retrieves oembed information for twitter/x', function () {
    $this->fakeResponses();
    $provider = new \Webwizardsusa\OEmbed\Providers\TwitterProvider();
    $response = $provider->extract(new \Webwizardsusa\OEmbed\OEmbedUrl($this->getUrl('twitter')));
    $this->assertInstanceOf(\Webwizardsusa\OEmbed\OEmbedResponse::class, $response);
    $this->assertEquals('twitter', $response->getProvider());
});

it('retrieves oembed information for youtube', function () {
    $this->fakeResponses();
    $provider = new \Webwizardsusa\OEmbed\Providers\YoutubeProvider();
    $response = $provider->extract(new \Webwizardsusa\OEmbed\OEmbedUrl($this->getUrl('youtube')));
    $this->assertInstanceOf(\Webwizardsusa\OEmbed\OEmbedResponse::class, $response);
    $this->assertEquals('youtube', $response->getProvider());
});

it('retrieves oembed information for bluesky', function () {
    $provider = new \Webwizardsusa\OEmbed\Providers\BlueskyProvider();
    $response = $provider->extract(new \Webwizardsusa\OEmbed\OEmbedUrl($this->getUrl('bluesky')));
    $this->assertInstanceOf(\Webwizardsusa\OEmbed\OEmbedResponse::class, $response);
    $this->assertEquals('bluesky', $response->getProvider());
});


it('retrieves oembed information for vimeo', function () {
    $provider = new \Webwizardsusa\OEmbed\Providers\VimeoProvider();
    $response = $provider->extract(new \Webwizardsusa\OEmbed\OEmbedUrl($this->getUrl('vimeo')));
    $this->assertInstanceOf(\Webwizardsusa\OEmbed\OEmbedResponse::class, $response);
    $this->assertEquals('vimeo', $response->getProvider());
});

it('retrieves oembed information for dailymotion', function () {
    $provider = new \Webwizardsusa\OEmbed\Providers\DailymotionProvider();
    $response = $provider->extract(new \Webwizardsusa\OEmbed\OEmbedUrl($this->getUrl('dailymotion')));
    $this->assertInstanceOf(\Webwizardsusa\OEmbed\OEmbedResponse::class, $response);
    $this->assertEquals('dailymotion', $response->getProvider());
});

//https://www.flickr.com/photos/laraveltw/44191083634/


it('retrieves oembed information for flickr', function () {
    $provider = new \Webwizardsusa\OEmbed\Providers\FilckrProvider();
    $response = $provider->extract(new \Webwizardsusa\OEmbed\OEmbedUrl($this->getUrl('flickr')));
    $this->assertInstanceOf(\Webwizardsusa\OEmbed\OEmbedResponse::class, $response);
    $this->assertEquals('flickr', $response->getProvider());
});

it('retrieves oembed information for soundcloud', function () {
    $provider = new \Webwizardsusa\OEmbed\Providers\SoundcloudProvider();
    $response = $provider->extract(new \Webwizardsusa\OEmbed\OEmbedUrl($this->getUrl('soundcloud')));
    $this->assertInstanceOf(\Webwizardsusa\OEmbed\OEmbedResponse::class, $response);

    $this->assertEquals('soundcloud', $response->getProvider());
});


it('retrieves oembed information for scribd', function () {
    $provider = new \Webwizardsusa\OEmbed\Providers\ScribdProvider();
    $response = $provider->extract(new \Webwizardsusa\OEmbed\OEmbedUrl($this->getUrl('scribd')));
    $this->assertInstanceOf(\Webwizardsusa\OEmbed\OEmbedResponse::class, $response);
    $this->assertEquals('scribd', $response->getProvider());
});

it('retrieves oembed information for spotify', function () {
    $provider = new \Webwizardsusa\OEmbed\Providers\SpotifyProvider();
    $response = $provider->extract(new \Webwizardsusa\OEmbed\OEmbedUrl($this->getUrl('spotify')));
    $this->assertInstanceOf(\Webwizardsusa\OEmbed\OEmbedResponse::class, $response);
    $this->assertEquals('spotify', $response->getProvider());
});

it('retrieves oembed information for tumblr', function () {
    $provider = new \Webwizardsusa\OEmbed\Providers\TumblrProvider();
    $response = $provider->extract(new \Webwizardsusa\OEmbed\OEmbedUrl($this->getUrl('tumblr')));
    $this->assertInstanceOf(\Webwizardsusa\OEmbed\OEmbedResponse::class, $response);
    $this->assertEquals('tumblr', $response->getProvider());
});

it('retrieves oembed information for tiktok', function () {
    $provider = new \Webwizardsusa\OEmbed\Providers\TiktokProvider();
    $response = $provider->extract(new \Webwizardsusa\OEmbed\OEmbedUrl($this->getUrl('tiktok')));
    $this->assertInstanceOf(\Webwizardsusa\OEmbed\OEmbedResponse::class, $response);
    $this->assertEquals('tiktok', $response->getProvider());
});

it('retrieves oembed information for pinterest', function () {
    $provider = new \Webwizardsusa\OEmbed\Providers\PinterestProvider();
    $response = $provider->extract(new \Webwizardsusa\OEmbed\OEmbedUrl($this->getUrl('pinterest')));
    $this->assertInstanceOf(\Webwizardsusa\OEmbed\OEmbedResponse::class, $response);
    $this->assertEquals('pinterest', $response->getProvider());
});

it('retrieves oembed information for amazon', function () {
    $provider = new \Webwizardsusa\OEmbed\Providers\AmazonProvider();
    $response = $provider->extract(new \Webwizardsusa\OEmbed\OEmbedUrl($this->getUrl('amazon')));
    $this->assertInstanceOf(\Webwizardsusa\OEmbed\OEmbedResponse::class, $response);
    $this->assertEquals('amazon', $response->getProvider());
});


// file_put_contents(__DIR__ . '/Support/Responses/dailymotion.json', json_encode($response->raw()));
