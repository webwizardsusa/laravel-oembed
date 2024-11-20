<?php

namespace Webwizardsusa\OEmbed\Facade;

use Illuminate\Support\Facades\Facade;

class OEmbed extends Facade
{
    public static function getFacadeAccessor(): string
    {
        return 'oembed';
    }
}
