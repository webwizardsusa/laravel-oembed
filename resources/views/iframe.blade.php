@php
    /**
     * @var \Webwizardsusa\LaravelOembed\OembedResponse $response
     */
@endphp
<div class="laravel-oembed oembed-iframe oembed-provider-{{ $response->getProvider() }}">
    {!! $response->embedCode() !!}
</div>
