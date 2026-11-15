<?php

namespace Webwizardsusa\OEmbed;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Webwizardsusa\OEmbed\Providers\Provider;

/**
 * Asks providers' oEmbed endpoints about URLs, all at once. Only the provider's own endpoint is ever requested, never
 * the URL being embedded, and redirects are not followed.
 */
class OEmbedClient
{
    /**
     * Answers larger than this are refused.
     */
    public const int MAX_RESPONSE_BYTES = 512 * 1024;

    public function __construct(
        protected int $timeout = 3,
        protected int $concurrency = 10,
    ) {}

    public static function fromConfig(): static
    {
        return new static(timeout: (int) config('oembed.timeout', 3));
    }

    /**
     * @param  array<array-key, array{provider: Provider, url: string}>  $requests
     * @return array<array-key, ?OEmbedResponse> The same keys. Null for an answer that failed or isn't usable oEmbed.
     */
    public function fetchMany(array $requests, ?int $maxWidth = null): array
    {
        $answers = array_fill_keys(array_keys($requests), null);
        $sendable = array_filter($requests, static fn (array $request): bool => $request['provider']->getEndpoint() !== null);

        if ($sendable === []) {
            return $answers;
        }

        // Pool keys must be strings; the position in $sendable maps back to the caller's key.
        $keys = array_keys($sendable);

        $responses = Http::pool(fn (Pool $pool): array => array_map(
            fn (int $index): mixed => $this->send($pool->as("r{$index}"), $sendable[$keys[$index]], $maxWidth),
            array_keys($keys),
        ), concurrency: $this->concurrency);

        foreach ($keys as $index => $key) {
            $answers[$key] = $this->toResponse($responses["r{$index}"] ?? null);
        }

        return $answers;
    }

    public function fetch(Provider $provider, string $url, ?int $maxWidth = null): ?OEmbedResponse
    {
        return $this->fetchMany([['provider' => $provider, 'url' => $url]], $maxWidth)[0];
    }

    /**
     * @param  array{provider: Provider, url: string}  $request
     */
    protected function send(PendingRequest $pending, array $request, ?int $maxWidth): mixed
    {
        $provider = $request['provider'];

        return $pending
            ->acceptJson()
            ->timeout($this->timeout)
            ->connectTimeout($this->timeout)
            ->withOptions(['allow_redirects' => false])
            ->get($provider->getEndpoint(), array_filter([
                ...$provider->getQuery(),
                'url' => $request['url'],
                'format' => 'json',
                'maxwidth' => $maxWidth,
            ], static fn (mixed $value): bool => $value !== null));
    }

    protected function toResponse(mixed $response): ?OEmbedResponse
    {
        if (! $response instanceof Response || ! $response->successful() || strlen($response->body()) > static::MAX_RESPONSE_BYTES) {
            return null;
        }

        $data = json_decode($response->body(), true);

        return is_array($data) ? OEmbedResponse::fromArray($data) : null;
    }
}
