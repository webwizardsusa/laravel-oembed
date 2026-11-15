<?php

namespace Webwizardsusa\OEmbed\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Foundation\Application;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Webwizardsusa\OEmbed\OEmbedServiceProvider;

/**
 * Boots the package, and Filament and Livewire around it when they are installed: Filament is optional, and the CI
 * runs the suite without it too.
 */
abstract class TestCase extends BaseTestCase
{
    public static function hasFilament(): bool
    {
        return class_exists(FilamentServiceProvider::class);
    }

    /**
     * @param  Application  $app
     * @return array<class-string>
     */
    protected function getPackageProviders($app): array
    {
        $filament = static::hasFilament() ? [
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            LivewireServiceProvider::class,
            SupportServiceProvider::class,
            ActionsServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            NotificationsServiceProvider::class,
            SchemasServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            FilamentServiceProvider::class,
        ] : [];

        return [...$filament, OEmbedServiceProvider::class];
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('cache.default', 'array');
    }
}
