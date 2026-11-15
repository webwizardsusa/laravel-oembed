<?php

namespace Webwizardsusa\OEmbed\Commands;

use Illuminate\Console\Command;
use Webwizardsusa\OEmbed\Stores\DatabaseStore;
use Webwizardsusa\OEmbed\Stores\EmbedStoreManager;

/**
 * Deletes expired answers from the database store. Schedule it daily when you use that store.
 */
class PruneCommand extends Command
{
    protected $signature = 'oembed:prune';

    protected $description = 'Delete expired oEmbed answers from the database store';

    public function handle(EmbedStoreManager $stores): int
    {
        $store = $stores->store('database');

        if (! $store instanceof DatabaseStore) {
            $this->components->error('The database store has been replaced with one that cannot be pruned.');

            return self::FAILURE;
        }

        $this->components->info("Deleted {$store->prune()} expired oEmbed answers.");

        return self::SUCCESS;
    }
}
