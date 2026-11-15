<?php

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Component;
use Livewire\Livewire;
use Webwizardsusa\OEmbed\Embedder;
use Webwizardsusa\OEmbed\Filament\Forms\Components\OEmbedInput;
use Webwizardsusa\OEmbed\Filament\Infolists\Components\OEmbedEntry;
use Webwizardsusa\OEmbed\Filament\Tables\Columns\OEmbedColumn;
use Webwizardsusa\OEmbed\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! TestCase::hasFilament()) {
        $this->markTestSkipped('Filament is not installed.');
    }

    Http::preventStrayRequests();
    Http::fake([
        'www.youtube.com/oembed*' => fn (Request $request) => Http::response([
            'type' => 'video', 'title' => "Video {$request['url']}", 'thumbnail_url' => 'https://i.ytimg.com/vi/abc/hq.jpg',
            'width' => 640, 'height' => 360, 'html' => '<iframe src="https://www.youtube.com/embed/abc"></iframe>',
        ]),
        'vimeo.com/api/oembed.json*' => Http::response([], 404),
    ]);
});

if (class_exists(Component::class) && interface_exists(HasSchemas::class)) {
    class OEmbedTestForm extends Component implements HasActions, HasSchemas
    {
        use InteractsWithActions;
        use InteractsWithSchemas;

        /**
         * @var array<string, mixed> | null
         */
        public ?array $data = [];

        public string $preview = 'card';

        public function mount(): void
        {
            $this->form->fill();
        }

        public function form(Schema $schema): Schema
        {
            return $schema
                ->components([OEmbedInput::make('video')->label('Video')->helperText('Paste a video link')->preview($this->preview === 'none' ? false : $this->preview)])
                ->statePath('data');
        }

        public function save(): void
        {
            $this->form->getState();
        }

        public function render(): string
        {
            return '<div>{{ $this->form }}</div>';
        }
    }

    class OEmbedTestInfolist extends Component implements HasSchemas
    {
        use InteractsWithSchemas;

        public string $url = 'https://youtu.be/abc';

        public function infolist(Schema $schema): Schema
        {
            return $schema
                ->state(['video' => $this->url])
                ->components([OEmbedEntry::make('video')->privacyEnhancedYouTube(), TextEntry::make('video')->label('URL')]);
        }

        public function render(): string
        {
            return '<div>{{ $this->infolist }}</div>';
        }
    }

    class OEmbedTestTable extends Component implements HasActions, HasSchemas, HasTable
    {
        use InteractsWithActions;
        use InteractsWithSchemas;
        use InteractsWithTable;

        public function table(Table $table): Table
        {
            return $table
                ->records(fn (): array => [
                    1 => ['id' => 1, 'video' => 'https://youtu.be/a'],
                    2 => ['id' => 2, 'video' => 'https://youtu.be/b'],
                    3 => ['id' => 3, 'video' => 'https://vimeo.com/1'],
                ])
                ->columns([OEmbedColumn::make('video')]);
        }

        public function render(): string
        {
            return '<div>{{ $this->table }}</div>';
        }
    }
}

it('stores the URL and shows a preview card for it under the helper text', function (): void {
    Livewire::test(OEmbedTestForm::class)
        ->fillForm(['video' => 'https://youtu.be/abc'])
        ->assertSee('Paste a video link')
        ->assertHasNoFormErrors()
        ->assertSchemaStateSet(['video' => 'https://youtu.be/abc'])
        ->assertSeeHtml('<span class="oembed-card-title">Video https://youtu.be/abc</span>')
        ->assertSeeHtml('<img src="https://i.ytimg.com/vi/abc/hq.jpg"');
});

it('shows the embed itself as the preview, or no preview', function (): void {
    Livewire::test(OEmbedTestForm::class, ['preview' => 'embed'])
        ->fillForm(['video' => 'https://youtu.be/abc'])
        ->assertSeeHtml('<iframe src="https://www.youtube.com/embed/abc"');

    Livewire::test(OEmbedTestForm::class, ['preview' => 'none'])
        ->fillForm(['video' => 'https://youtu.be/abc'])
        ->assertDontSeeHtml('oembed-card')
        ->assertDontSeeHtml('<iframe');
});

it('refuses a URL it cannot embed', function (string $url, string $message): void {
    Livewire::test(OEmbedTestForm::class)
        ->fillForm(['video' => $url])
        ->call('save')
        ->assertHasFormErrors(['video'])
        ->assertSee(__($message));
})->with([
    'a site no provider takes' => ['https://example.com/page', 'oembed::oembed.validation.unsupported'],
    'a page the provider has nothing for' => ['https://vimeo.com/1', 'oembed::oembed.validation.unavailable'],
]);

it('accepts an empty field unless it is required', function (): void {
    Livewire::test(OEmbedTestForm::class)
        ->fillForm(['video' => null])
        ->call('save')
        ->assertHasNoFormErrors();

    Http::assertNothingSent();
});

it('shows the embed in an infolist entry, with the entry\'s own settings', function (): void {
    Livewire::test(OEmbedTestInfolist::class)
        ->assertSeeHtml('<iframe src="https://www.youtube-nocookie.com/embed/abc"');
});

it('shows each row\'s thumbnail and title in a table column, looking the whole page up at once', function (): void {
    Livewire::test(OEmbedTestTable::class)
        ->assertSeeHtml('<span class="oembed-column-title">Video https://youtu.be/a</span>')
        ->assertSeeHtml('<span class="oembed-column-title">Video https://youtu.be/b</span>')
        ->assertSeeHtml('<span class="oembed-column-title">https://vimeo.com/1</span>');

    Http::assertSentCount(3);
});

it('changes an embedder setting through a callback', function (): void {
    $input = OEmbedInput::make('video')->embedder(fn (Embedder $embedder) => $embedder->maxWidth(320));

    expect($input->getEmbedder()->getMaxWidth())->toBe(320);
});
