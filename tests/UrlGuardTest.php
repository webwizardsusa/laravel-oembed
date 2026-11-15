<?php

use Webwizardsusa\OEmbed\UrlGuard;

/**
 * A guard whose DNS answers come from the map, so no test does a real lookup.
 *
 * @param  array<string, array<string>>  $dns
 */
function guardWithDns(array $dns = []): UrlGuard
{
    return new UrlGuard(static fn (string $host): array => $dns[$host] ?? []);
}

it('allows an http(s) URL whose host has only public addresses, and answers the address to connect to', function (): void {
    $guard = guardWithDns(['example.com' => ['93.184.216.34', '2606:2800:220:1:248:1893:25c8:1946']]);

    expect($guard->check('https://example.com/videos/1'))->toBe(['host' => 'example.com', 'port' => 443, 'ip' => '93.184.216.34'])
        ->and($guard->check('http://Example.COM./'))->toBe(['host' => 'example.com', 'port' => 80, 'ip' => '93.184.216.34'])
        ->and($guard->check('https://93.184.216.34/'))->toBe(['host' => '93.184.216.34', 'port' => 443, 'ip' => '93.184.216.34']);
});

it('refuses URLs that reach the server\'s own network or are not plain web URLs', function (string $url): void {
    $guard = guardWithDns([
        'example.com' => ['93.184.216.34'],
        'rebind.example.com' => ['10.0.0.5'],
        'mixed.example.com' => ['93.184.216.34', '127.0.0.1'],
        'metadata.example.com' => ['169.254.169.254'],
        'v6.example.com' => ['fd00::1'],
    ]);

    expect($guard->check($url))->toBeNull();
})->with([
    'loopback' => 'http://127.0.0.1/',
    'private' => 'http://10.0.0.1/',
    'cloud metadata' => 'http://169.254.169.254/latest/meta-data/',
    'shared address space' => 'http://100.64.0.1/',
    'this network' => 'http://0.0.0.0/',
    'IPv6 loopback' => 'http://[::1]/',
    'IPv6 unique local' => 'http://[fd00::1]/',
    'IPv4 inside IPv6' => 'http://[::ffff:127.0.0.1]/',
    'a decimal address' => 'http://2130706433/',
    'a hex address' => 'http://0x7f000001/',
    'a short address' => 'http://127.1/',
    'localhost' => 'http://localhost/',
    'a .localhost name' => 'http://app.localhost/',
    'a .local name' => 'http://printer.local/',
    'a .internal name' => 'http://db.internal/',
    'a single-label name' => 'http://intranet/',
    'a name resolving to a private address' => 'https://rebind.example.com/',
    'a name with one private address among public ones' => 'https://mixed.example.com/',
    'a name resolving to the metadata address' => 'https://metadata.example.com/',
    'a name resolving to a private IPv6 address' => 'https://v6.example.com/',
    'a name that does not resolve' => 'https://nowhere.example.com/',
    'another port' => 'https://example.com:8443/',
    'the http port on https' => 'https://example.com:80/',
    'credentials' => 'https://user:secret@example.com/',
    'another scheme' => 'ftp://example.com/',
    'no host' => 'https:///path',
]);

it('pins the request to the checked address', function (): void {
    expect(UrlGuard::pinnedCurlOptions(['host' => 'example.com', 'port' => 443, 'ip' => '93.184.216.34']))
        ->toBe([CURLOPT_RESOLVE => ['example.com:443:93.184.216.34']])
        ->and(UrlGuard::pinnedCurlOptions(['host' => 'example.com', 'port' => 80, 'ip' => '2606:2800::1']))
        ->toBe([CURLOPT_RESOLVE => ['example.com:80:[2606:2800::1]']]);
});
