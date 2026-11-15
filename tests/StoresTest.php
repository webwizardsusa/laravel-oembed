<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Webwizardsusa\OEmbed\Embedder;
use Webwizardsusa\OEmbed\Facades\OEmbed;
use Webwizardsusa\OEmbed\OEmbedResponse;
use Webwizardsusa\OEmbed\Stores\CacheStore;
use Webwizardsusa\OEmbed\Stores\EmbedStore;
use Webwizardsusa\OEmbed\Stores\EmbedStoreManager;
use Webwizardsusa\OEmbed\Stores\StoreKey;
use Webwizardsusa\OEmbed\Tests\TestCase;

uses(TestCase::class);

/**
 * Creates the database store's table, as the published migration does.
 */
function migrateOEmbedTable(): void
{
    (require dirname(__DIR__).'/database/migrations/create_oembed_responses_table.php.stub')->up();
}

function fakeYouTube(int $status = 200): void
{
    Http::preventStrayRequests();
    Http::fake(['www.youtube.com/oembed*' => Http::response($status === 200 ? ['type' => 'video', 'title' => 'A video', 'html' => '<iframe></iframe>'] : [], $status)]);
}

/**
 * A store that keeps answers in memory, as an app's own store might.
 */
class MemoryEmbedStore implements EmbedStore
{
    /**
     * @var array<string, array{url: string, response: OEmbedResponse|false}>
     */
    public array $items = [];

    public function getMany(array $keys): array
    {
        return array_map(static fn (array $item): OEmbedResponse|false => $item['response'], array_intersect_key($this->items, array_flip($keys)));
    }

    public function put(string $key, string $url, ?OEmbedResponse $response): void
    {
        $this->items[$key] = ['url' => $url, 'response' => $response ?? false];
    }

    public function forget(string $key): void
    {
        unset($this->items[$key]);
    }
}

it('keeps answers in the cache by default', function (): void {
    expect(Embedder::make()->getStore())->toBeInstanceOf(CacheStore::class);
});

it('keeps answers, and failed lookups, in the database store', function (): void {
    migrateOEmbedTable();
    config(['oembed.store' => 'database']);
    fakeYouTube();

    expect(Embedder::make()->resolve('https://youtu.be/a')?->title())->toBe('A video')
        ->and(Embedder::make()->resolve('https://youtu.be/a')?->title())->toBe('A video');

    Http::assertSentCount(1);

    $row = DB::table('oembed_responses')->first();

    expect($row->key)->toBe(StoreKey::make('youtube', 'https://youtu.be/a', 800))
        ->and($row->url)->toBe('https://youtu.be/a')
        ->and(json_decode($row->response, true)['title'])->toBe('A video');
});

it('looks an answer up again once it expires, and a failed lookup sooner', function (): void {
    migrateOEmbedTable();
    config(['oembed.store' => 'database', 'oembed.ttl' => 3600, 'oembed.failure_ttl' => 60]);
    $this->freezeTime();
    Http::preventStrayRequests();
    Http::fake(['www.youtube.com/oembed*' => Http::sequence()
        ->push([], 404)
        ->push(['type' => 'video', 'title' => 'A video', 'html' => '<iframe></iframe>'])
        ->push(['type' => 'video', 'title' => 'A newer video', 'html' => '<iframe></iframe>'])]);

    expect(Embedder::make()->resolve('https://youtu.be/a'))->toBeNull()
        ->and(DB::table('oembed_responses')->value('response'))->toBeNull();

    $this->travel(30)->seconds();
    expect(Embedder::make()->resolve('https://youtu.be/a'))->toBeNull();
    Http::assertSentCount(1);

    $this->travel(31)->seconds();
    expect(Embedder::make()->resolve('https://youtu.be/a')?->title())->toBe('A video');

    $this->travel(3599)->seconds();
    expect(Embedder::make()->resolve('https://youtu.be/a')?->title())->toBe('A video');

    $this->travel(2)->seconds();
    expect(Embedder::make()->resolve('https://youtu.be/a')?->title())->toBe('A newer video')
        ->and(DB::table('oembed_responses')->count())->toBe(1);

    Http::assertSentCount(3);
});

it('prunes expired answers from the database store', function (): void {
    migrateOEmbedTable();
    config(['oembed.ttl' => 3600]);
    $this->freezeTime();

    $store = app(EmbedStoreManager::class)->store('database');
    $store->put('old', 'https://youtu.be/old', OEmbedResponse::fromArray(['type' => 'link']));
    $this->travel(3601)->seconds();
    $store->put('new', 'https://youtu.be/new', OEmbedResponse::fromArray(['type' => 'link']));

    Artisan::call('oembed:prune');

    expect(DB::table('oembed_responses')->pluck('key')->all())->toBe(['new']);
});

it('forgets an answer', function (string $store): void {
    if ($store === 'database') {
        migrateOEmbedTable();
    }

    $embedStore = app(EmbedStoreManager::class)->store($store);
    $embedStore->put('a', 'https://youtu.be/a', OEmbedResponse::fromArray(['type' => 'link']));
    $embedStore->forget('a');

    expect($embedStore->getMany(['a']))->toBe([]);
})->with(['cache', 'database']);

it('uses the store an embedder is given, whatever the default', function (): void {
    migrateOEmbedTable();
    fakeYouTube();

    Embedder::make()->store('database')->resolve('https://youtu.be/a');

    expect(DB::table('oembed_responses')->count())->toBe(1)
        ->and(app(EmbedStoreManager::class)->store('cache')->getMany([StoreKey::make('youtube', 'https://youtu.be/a', 800)]))->toBe([]);
});

it('keeps answers in a store an app adds', function (): void {
    $store = new MemoryEmbedStore;
    OEmbed::extend('memory', fn (): EmbedStore => $store);
    config(['oembed.store' => 'memory']);
    fakeYouTube();

    Embedder::make()->resolve('https://youtu.be/a');
    Embedder::make()->resolve('https://youtu.be/a');

    expect(array_column($store->items, 'url'))->toBe(['https://youtu.be/a']);
    Http::assertSentCount(1);
});

it('publishes the database store\'s migration, and never runs it from the package', function (): void {
    expect(Schema::hasTable('oembed_responses'))->toBeFalse()
        ->and(ServiceProvider::pathsToPublish(null, 'oembed-migrations'))->not->toBeEmpty()
        ->and(app('migrator')->paths())->each->not->toContain('laravel-oembed');
});

it('only prunes a database store', function (): void {
    OEmbed::extend('database', fn (): EmbedStore => new MemoryEmbedStore);

    expect(Artisan::call('oembed:prune'))->toBe(1);
});
