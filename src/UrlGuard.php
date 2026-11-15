<?php

namespace Webwizardsusa\OEmbed;

use Closure;

/**
 * Decides whether the server may fetch a URL someone typed or pasted, so discovery can't be pointed at the server's
 * own network: loopback, private, link-local (cloud metadata) or any other non-public address.
 *
 * Only http(s) on the default ports, without credentials, to a host whose every address is public. A host given as
 * an IP must be a public one in standard notation; hosts that only look like numbers (`2130706433`, `0x7f.1`, `127.1`)
 * and names that are local by definition (`localhost`, `*.local`, `*.internal`…) are refused before any lookup.
 *
 * {@see check()} answers the address to connect to, and the request must be pinned to it ({@see pinnedCurlOptions()}),
 * so DNS can't answer a public address here and a private one when the request connects.
 */
class UrlGuard
{
    protected const array PORTS = ['http' => 80, 'https' => 443];

    protected const array LOCAL_SUFFIXES = ['localhost', 'local', 'internal', 'intranet', 'lan', 'home', 'corp', 'localdomain', 'home.arpa'];

    /**
     * @param  (Closure(string): array<string>) | null  $resolveUsing  The host's IPv4 and IPv6 addresses.
     */
    public function __construct(protected ?Closure $resolveUsing = null) {}

    /**
     * @return array{host: string, port: int, ip: string} | null The address to connect to, or null when the URL may
     *                                                           not be fetched.
     */
    public function check(string $url): ?array
    {
        $parts = parse_url(trim($url));

        if (! is_array($parts) || isset($parts['user']) || isset($parts['pass']) || ! isset($parts['host'])) {
            return null;
        }

        $scheme = strtolower($parts['scheme'] ?? '');
        $port = $parts['port'] ?? static::PORTS[$scheme] ?? null;

        if (! isset(static::PORTS[$scheme]) || $port !== static::PORTS[$scheme]) {
            return null;
        }

        $host = rtrim(strtolower($parts['host']), '.');
        $literal = trim($host, '[]');

        if (filter_var($literal, FILTER_VALIDATE_IP) !== false) {
            return static::isPublicIp($literal) ? ['host' => $host, 'port' => $port, 'ip' => $literal] : null;
        }

        if (! static::isPublicHostName($host)) {
            return null;
        }

        $ips = $this->resolve($host);

        if ($ips === [] || array_filter($ips, static fn (string $ip): bool => ! static::isPublicIp($ip)) !== []) {
            return null;
        }

        return ['host' => $host, 'port' => $port, 'ip' => $ips[0]];
    }

    /**
     * Curl options that make the request connect to the checked address, whatever DNS answers by then.
     *
     * @param  array{host: string, port: int, ip: string}  $target
     * @return array<int, mixed>
     */
    public static function pinnedCurlOptions(array $target): array
    {
        if (str_starts_with($target['host'], '[')) {
            return [];
        }

        $ip = str_contains($target['ip'], ':') ? "[{$target['ip']}]" : $target['ip'];

        return [CURLOPT_RESOLVE => ["{$target['host']}:{$target['port']}:{$ip}"]];
    }

    /**
     * Public, globally routable addresses only. `FILTER_FLAG_GLOBAL_RANGE` refuses private, loopback, link-local,
     * shared (100.64/10), documentation, benchmarking and reserved ranges, for IPv4 and IPv6. IPv4 addresses inside
     * IPv6 (`::ffff:127.0.0.1`) are checked as IPv4.
     */
    public static function isPublicIp(string $ip): bool
    {
        if (preg_match('/^::ffff:(\d{1,3}(?:\.\d{1,3}){3})$/i', $ip, $matches) === 1) {
            $ip = $matches[1];
        }

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE) !== false;
    }

    protected static function isPublicHostName(string $host): bool
    {
        // Every label is a plain hostname label, and the last one has a letter: `127.1`, `2130706433` or `0x7f.0.0.1`
        // are addresses some resolvers accept, and none of them are names.
        if (preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9-]{2,63}$/', $host) !== 1
            || preg_match('/^(?:\d+|0x[0-9a-f]+)$/', substr($host, strrpos($host, '.') + 1)) === 1) {
            return false;
        }

        foreach (static::LOCAL_SUFFIXES as $suffix) {
            if ($host === $suffix || str_ends_with($host, ".{$suffix}")) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string>
     */
    protected function resolve(string $host): array
    {
        if ($this->resolveUsing !== null) {
            return array_values(array_filter(($this->resolveUsing)($host), is_string(...)));
        }

        $ips = gethostbynamel($host) ?: [];
        $records = @dns_get_record($host, DNS_AAAA) ?: [];

        foreach ($records as $record) {
            if (is_string($record['ipv6'] ?? null)) {
                $ips[] = $record['ipv6'];
            }
        }

        return array_values(array_unique($ips));
    }
}
