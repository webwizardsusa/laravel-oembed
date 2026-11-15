<?php

namespace Webwizardsusa\OEmbed;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Blade;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Webwizardsusa\OEmbed\View\Components\OEmbed;

/**
 * Registers the config (`config/oembed.php`), translations and the `<x-oembed>` Blade component. With Filament
 * installed, it also registers the stylesheets the Filament field, entry and column use.
 */
class OEmbedServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('oembed')
            ->hasConfigFile()
            ->hasTranslations();
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
