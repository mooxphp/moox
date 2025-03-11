<?php

namespace Moox\Media\Resources;

use Filament\Facades\Filament;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Media\Forms\Components\ImageDisplay;
use Moox\Media\Models\Media;
use Moox\Media\Resources\MediaResource\Pages;
use Moox\Media\Tables\Columns\CustomImageColumn;

class MediaResource extends Resource
{
    use BaseInResource;

    protected static ?string $model = Media::class;

    protected static ?string $navigationIcon = 'gmdi-view-timeline-o';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'Medien';

    protected static ?string $pluralModelLabel = 'Medien';

    public static function form(Form $form): Form
    {
        $saveRecord = function ($state, $old, $component) use ($form) {
            $record = $form->getRecord();
            if ($state !== $old) {
                $record->{$component->getName()} = $state;
                $record->save();
            }
        };

        return $form->schema([
            ImageDisplay::make('')
                ->columnSpanFull(),

            Section::make()
                ->schema([
                    Placeholder::make('mime_type')
                        ->label('Dateityp')
                        ->content(fn ($record) => $record->getReadableMimeType()),

                    Placeholder::make('size')
                        ->label('Dateigröße')
                        ->content(fn ($record) => number_format($record->size / 1024, 2).' KB'),

                    Placeholder::make('file_name')
                        ->label('Originaldateiname')
                        ->content(fn ($record) => $record->file_name),

                    Placeholder::make('dimensions')
                        ->label('Abmessungen')
                        ->content(function ($record) {
                            $dimensions = $record->getCustomProperty('dimensions');
                            if (! $dimensions) {
                                return '-';
                            }

                            return "{$dimensions['width']} × {$dimensions['height']} Pixel";
                        })
                        ->visible(fn ($record) => str_starts_with($record->mime_type, 'image/')),

                    Placeholder::make('created_at')
                        ->label('Hochgeladen am')
                        ->content(fn ($record) => $record->created_at?->format('d.m.Y H:i')),

                    Placeholder::make('updated_at')
                        ->label('Zuletzt bearbeitet')
                        ->content(fn ($record) => $record->updated_at?->format('d.m.Y H:i')),

                    Placeholder::make('usage')
                        ->label('Verwendet in')
                        ->content(function ($record) {
                            $usages = \DB::table('media_usables')
                                ->where('media_id', $record->id)
                                ->get();

                            if ($usages->isEmpty()) {
                                return 'Nicht verwendet';
                            }

                            $links = $usages->map(function ($usage) {
                                $type = Str::plural(strtolower(class_basename($usage->media_usable_type)));
                                $url = Filament::getCurrentPanel()->getUrl().'/'.$type.'/'.$usage->media_usable_id;

                                return Blade::render('<a href="{{ $url }}" target="_blank" class="text-primary underline">{{ $url }}</a>', [
                                    'url' => $url,
                                ]);
                            })->join('<br>');

                            return RawJs::make($links);
                        }),
                ])
                ->columns(4),

            Section::make('Metadaten')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated($saveRecord),

                    TextInput::make('title')
                        ->live(onBlur: true)
                        ->afterStateUpdated($saveRecord),

                    TextInput::make('alt')
                        ->label('Alt Text')
                        ->live(onBlur: true)
                        ->afterStateUpdated($saveRecord),

                    Textarea::make('description')
                        ->live(onBlur: true)
                        ->afterStateUpdated($saveRecord),
                ])
                ->columns(2)
                ->collapsed(),

            Section::make('Interne Notizen')
                ->schema([
                    TextInput::make('internal_note')
                        ->live(onBlur: true)
                        ->afterStateUpdated($saveRecord),
                ])
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Stack::make([
                    CustomImageColumn::make('')
                        ->extraImgAttributes([
                            'class' => 'rounded-lg',
                            'style' => 'width: 100%; height: auto; min-width: 150px; max-width: 250px; aspect-ratio: 1/1; object-fit: cover;',
                        ])
                        ->tooltip(fn ($record) => $record->title ?? 'Kein Titel')
                        ->searchable(['name', 'title', 'description', 'alt', 'internal_note']),
                ]),
            ])
            ->actions([
                EditAction::make()
                    ->icon('')
                    ->label('')
                    ->slideOver()
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Schließen')
                    ->extraModalFooterActions([
                        \Filament\Tables\Actions\Action::make('delete')
                            ->label('Löschen')
                            ->color('danger')
                            ->icon('heroicon-m-trash')
                            ->requiresConfirmation()
                            ->modalHeading(fn ($record) => 'Bild "'.($record->title ?: $record->name).'" löschen')
                            ->modalDescription('Sind Sie sicher, dass Sie dieses Bild löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.')
                            ->modalSubmitActionLabel('Ja, löschen')
                            ->modalCancelActionLabel('Abbrechen')
                            ->action(function ($record) {
                                Media::where('id', $record->id)->delete();

                                return redirect(static::getUrl('index'));
                            }),
                    ]),
            ])
            ->filters([
                SelectFilter::make('mime_type')
                    ->label('Dateityp')
                    ->options([
                        'images' => 'Bilder',
                        'videos' => 'Videos',
                        'audios' => 'Audios',
                        'documents' => 'Dokumente',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (! $data['value']) {
                            return $query;
                        }

                        $mimeTypes = [
                            'images' => [
                                'image/jpeg',
                                'image/png',
                                'image/webp',
                                'image/svg+xml',
                                'image/gif',
                                'image/bmp',
                                'image/tiff',
                                'image/ico',
                                'image/heic',
                                'image/heif',
                            ],
                            'videos' => ['video/mp4', 'video/webm'],
                            'audios' => ['audio/mpeg', 'audio/ogg', 'audio/wav', 'audio/webm'],
                            'documents' => [
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-powerpoint',
                                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                            ],
                        ];

                        if (isset($mimeTypes[$data['value']])) {
                            $query->whereIn('mime_type', $mimeTypes[$data['value']]);
                        }

                        return $query;
                    }),
                SelectFilter::make('date')
                    ->label('Hochgeladen')
                    ->options([
                        'today' => 'Heute',
                        'week' => 'Diese Woche',
                        'month' => 'Diesen Monat',
                        'year' => 'Dieses Jahr',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (! $data['value']) {
                            return $query;
                        }

                        switch ($data['value']) {
                            case 'today':
                                $query->whereDate('created_at', today());
                                break;
                            case 'week':
                                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                                break;
                            case 'month':
                                $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
                                break;
                            case 'year':
                                $query->whereBetween('created_at', [now()->startOfYear(), now()->endOfYear()]);
                                break;
                        }

                        return $query;
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginationPageOptions([30, 60, 90])
            ->contentGrid([
                'md' => 2,
                'lg' => 3,
                'xl' => 5,
                '2xl' => 6,
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMedia::route('/'),
        ];
    }
}
