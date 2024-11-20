<?php

namespace Webwizardsusa\OEmbed;


use Illuminate\Support\ServiceProvider;

class OEmbedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/oembed.php', 'oembed');
        $this->app->alias(OEmbed::class, 'oembed');
        $this->app->singleton(OEmbed::class, function(){
            return new OEmbed(config('oembed.providers', []));
        });
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'laravel-oembed');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/oembed.php' => config_path('oembed.php'),
        ], ['oembed-config', 'oembed']);

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/laravel-oembed'),
        ], ['oembed-views', 'oembed']);
    }
}
