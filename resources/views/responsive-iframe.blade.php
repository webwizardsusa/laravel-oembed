@php
    /**
     * @var \Webwizardsusa\LaravelOembed\OembedResponse $response
     */
@endphp

@php
    /**
     * @var \Webwizardsusa\LaravelOembed\OembedResponse $response
     */
@endphp
<div class="laravel-oembed oembed-iframe oembed-responsive oembed-provider-{{ $response->getProvider() }}">
    <div class="oembed-responsive-container" style="padding-bottom: {{ $response->aspectRatio() }}%">
        {!! $response->embedCode() !!}
    </div>
</div>
