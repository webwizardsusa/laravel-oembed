<?php

namespace Webwizardsusa\OEmbed\Exceptions;

use Exception;
use Throwable;

class ProviderNotFoundException extends OembedException
{
    public function __construct(string $providerName, string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        if (!$message) {
            $message = 'OEmbed provider ' . $providerName . ' not found';
            $code = 404;
        }
        parent::__construct($message, $code, $previous);
    }
}
