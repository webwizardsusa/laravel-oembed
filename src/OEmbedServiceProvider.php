<?php

namespace Webwizardsusa\OEmbed;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Blade;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Webwizardsusa\OEmbed\Commands\PruneCommand;
use Webwizardsusa\OEmbed\Stores\EmbedStoreManager;
use Webwizardsusa\OEmbed\View\Components\OEmbed;

/**
 * Registers the config (`config/oembed.php`), translations, the `<x-oembed>` Blade component, the store manager and
 * the `oembed:prune` command. The database store's migration is only published (`--tag=oembed-migrations`), never
 * run from the package. With Filament installed, it also registers the stylesheets the Filament pieces use.
 */
class OEmbedServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('oembed')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigration('create_oembed_responses_table')
            ->hasCommand(PruneCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(EmbedStoreManager::class, static fn ($app): EmbedStoreManager => new EmbedStoreManager($app));
    }

    public function packageBooted(): void
    {
        Blade::component('oembed', OEmbed::class);

        if (class_exists(FilamentAsset::class)) {
            FilamentAsset::register([
                Css::make('oembed', __DIR__.'/../resources/css/oembed.css'),
                Css::make('oembed-filament', __DIR__.'/../resources/css/filament.css'),
            ], 'webwizardsusa/laravel-oembed');
        }
    }
}
