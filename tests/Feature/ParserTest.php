<?php

use function PHPUnit\Framework\assertNotEquals;

beforeEach(function () {
    $this->fakeResponses();
});

test('it returns oembed from url', function() {
    $oembed = \Webwizardsusa\OEmbed\UrlParser::make();
    foreach($this->urlMap() as $provider=>$urls) {
        foreach($urls as $url) {
            $result = $oembed->retrieve($url);
            $this->assertEquals($provider, $result->getProvider());
        }
    }
});

test('it caches urls', function() {
    $oembed = \Webwizardsusa\OEmbed\UrlParser::make();
    $result = $oembed->retrieve('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
    $originalTs = $result->retrievedAt();
    $this->assertInstanceOf(\Webwizardsusa\OEmbed\OEmbedResponse::class, $result);
    \Carbon\Carbon::setTestNow(\Carbon\Carbon::now()->addMinutes(1));
    $result = $oembed->retrieve('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
    $this->assertEquals($originalTs, $result->retrievedAt());
});

test('it expires cached urls', function() {

    $this->withoutExceptionHandling();
    $oembed = \Webwizardsusa\OEmbed\UrlParser::make();
    $url = 'https://example.com';

    $result = $oembed->retrieve($url);
    $this->assertNull($result);
    $this->assertTrue($oembed->cache()->has($oembed->makeCacheKey($url)));

});



