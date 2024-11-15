@php
    /**
     * @var \Webwizardsusa\LaravelOembed\OembedResponse $response
     */
@endphp
<div class="laravel-oembed oembed-html oembed-provider-{{ $response->getProvider() }}">
    {!! $response->embedCode() !!}
</div>
