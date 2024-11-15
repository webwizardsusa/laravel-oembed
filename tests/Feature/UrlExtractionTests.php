<?php

use Webwizardsusa\OEmbed\OEmbedUrl;

it('creates an oembed url class from a url string', function() {
   $url = 'https://example.com/test/path';
    $oembedUrl = OEmbedUrl::make($url);
   $this->assertEquals('example.com', $oembedUrl->host());
   $this->assertEquals('https', $oembedUrl->scheme());
   $this->assertTrue($oembedUrl->isSecure());
   $this->assertEquals('/test/path', $oembedUrl->path());
});

it('extracts query parameters', function() {
    $url = 'https://example.com/test/path?key1=test%20it&key2=1';
    $oembedUrl = OEmbedUrl::make($url);
    $this->assertEquals('test it', $oembedUrl->getQuery('key1'));
    $this->assertEquals('1', $oembedUrl->getQuery('key2'));
    $this->assertTrue($oembedUrl->hasQuery('key1'));
    $this->assertFalse($oembedUrl->hasQuery('key3'));
});

it('determines a host ends with a string', function() {
    $url = 'https://example.com/';
    $oembedUrl = OEmbedUrl::make($url);
    $this->assertTrue($oembedUrl->domainOf('example.com'));
    $this->assertFalse($oembedUrl->domainOf('example.org'));
    $this->assertTrue($oembedUrl->domainOf('EXAMPLE.com'));
    $this->assertFalse($oembedUrl->domainOf('EXAMPLE.com', false));

    $url = 'https://subdomain.example.com/';
    $oembedUrl = OEmbedUrl::make($url);
    $this->assertTrue($oembedUrl->domainOf('example.com'));
});
