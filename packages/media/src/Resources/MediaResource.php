<?php

namespace Moox\Media\Resources;

use Filament\Facades\Filament;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Media\Forms\Components\ImageDisplay;
use Moox\Media\Models\Media;
use Moox\Media\Resources\MediaResource\Pages;
use Moox\Media\Tables\Columns\CustomImageColumn;
use Filament\Tables\Actions\HeaderActionsPosition;

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
                        ->content(fn($record) => $record->getReadableMimeType()),

                    Placeholder::make('size')
                        ->label('Dateigröße')
                        ->content(fn($record) => number_format($record->size / 1024, 2) . ' KB'),

                    Placeholder::make('file_name')
                        ->label('Originaldateiname')
                        ->content(fn($record) => $record->file_name),

                    Placeholder::make('dimensions')
                        ->label('Abmessungen')
                        ->content(function ($record) {
                            $dimensions = $record->getCustomProperty('dimensions');
                            if (!$dimensions) {
                                return '-';
                            }

                            return "{$dimensions['width']} × {$dimensions['height']} Pixel";
                        })
                        ->visible(fn($record) => str_starts_with($record->mime_type, 'image/')),

                    Placeholder::make('created_at')
                        ->label('Hochgeladen am')
                        ->content(fn($record) => $record->created_at?->format('d.m.Y H:i')),

                    Placeholder::make('updated_at')
                        ->label('Zuletzt bearbeitet')
                        ->content(fn($record) => $record->updated_at?->format('d.m.Y H:i')),

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
                                $url = Filament::getCurrentPanel()->getUrl() . '/' . $type . '/' . $usage->media_usable_id;

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
                        ->extraImgAttributes(function ($record, $livewire) {
                            $baseStyle = 'width: 100%; height: auto; min-width: 150px; max-width: 250px; aspect-ratio: 1/1; object-fit: cover;';

                            if ($livewire->isSelecting) {
                                $style = $baseStyle . 'opacity: 0.5;';

                                if (in_array($record->id, $livewire->selected)) {
                                    $style = $baseStyle . 'outline: 4px solid rgb(59 130 246); opacity: 1;';
                                }

                                return [
                                    'class' => 'rounded-lg cursor-pointer',
                                    'style' => $style,
                                    'wire:click.stop' => "\$set('selected', " .
                                        (in_array($record->id, $livewire->selected)
                                            ? json_encode(array_values(array_diff($livewire->selected, [$record->id])))
                                            : json_encode(array_merge($livewire->selected, [$record->id]))
                                        ) . ")",
                                ];
                            }

                            return [
                                'class' => 'rounded-lg cursor-pointer',
                                'style' => $baseStyle,
                                'x-on:click' => '$wire.call("mountAction", "edit", { record: ' . $record->id . ' })',
                            ];
                        })
                        ->tooltip(fn($record) => $record->title ?? 'Kein Titel')
                        ->searchable(['name', 'title', 'description', 'alt', 'internal_note']),

                ]),
            ])
            ->headerActionsPosition(HeaderActionsPosition::Bottom)
            ->headerActions([
                \Filament\Tables\Actions\Action::make('toggleSelect')
                    ->label(function ($livewire) {
                        if ($livewire->isSelecting) {
                            $count = count($livewire->selected);

                            return $count > 0
                                ? "{$count} " . trans_choice('Medium|Medien', $count) . ' ausgewählt'
                                : 'Auswahl beenden';
                        }

                        return 'Mehrere auswählen';
                    })
                    ->icon(fn($livewire) => $livewire->isSelecting ? 'heroicon-m-x-mark' : 'heroicon-m-squares-2x2')
                    ->color(fn($livewire) => $livewire->isSelecting ? 'gray' : 'primary')
                    ->action(function ($livewire) {
                        $livewire->isSelecting = !$livewire->isSelecting;
                        $livewire->selected = [];
                    }),

                \Filament\Tables\Actions\Action::make('deleteSelected')
                    ->label('Ausgewählte löschen')
                    ->icon('heroicon-m-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Ausgewählte Medien löschen')
                    ->modalDescription('Sind Sie sicher, dass Sie die ausgewählten Medien löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.')
                    ->modalSubmitActionLabel('Ja, löschen')
                    ->modalCancelActionLabel('Abbrechen')
                    ->visible(fn($livewire) => $livewire->isSelecting && !empty($livewire->selected))
                    ->action(function ($livewire) {
                        $successCount = 0;
                        $errorCount = 0;

                        foreach ($livewire->selected as $id) {
                            try {
                                $media = Media::find($id);
                                if ($media) {
                                    $media->deletePreservingMedia();
                                    $media->delete();
                                    $successCount++;
                                }
                            } catch (\Exception $e) {
                                Log::error('Media deletion failed: ' . $e->getMessage(), [
                                    'media_id' => $id,
                                ]);
                                $errorCount++;
                            }
                        }

                        if ($successCount > 0) {
                            Notification::make()
                                ->success()
                                ->title($successCount . ' ' . trans_choice('Medium|Medien', $successCount) . ' gelöscht')
                                ->send();
                        }

                        if ($errorCount > 0) {
                            Notification::make()
                                ->danger()
                                ->title('Fehler beim Löschen')
                                ->body($errorCount . ' ' . trans_choice('Medium konnte|Medien konnten', $errorCount) . ' nicht gelöscht werden.')
                                ->send();
                        }

                        $livewire->isSelecting = false;
                        $livewire->selected = [];
                    }),
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
                            ->modalHeading(fn($record) => 'Bild "' . ($record->title ?: $record->name) . '" löschen')
                            ->modalDescription('Sind Sie sicher, dass Sie dieses Bild löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.')
                            ->modalSubmitActionLabel('Ja, löschen')
                            ->modalCancelActionLabel('Abbrechen')
                            ->action(function ($record) {
                                try {
                                    $fileName = $record->file_name;
                                    $record->deletePreservingMedia();
                                    $record->delete();

                                    Notification::make()
                                        ->success()
                                        ->title('Medium erfolgreich gelöscht')
                                        ->body('Die Datei "' . $fileName . '" wurde erfolgreich gelöscht.')
                                        ->send();

                                    return redirect(static::getUrl('index'));
                                } catch (\Exception $e) {
                                    Log::error('Media deletion failed: ' . $e->getMessage(), [
                                        'media_id' => $record->id,
                                        'file_name' => $record->file_name,
                                    ]);

                                    Notification::make()
                                        ->danger()
                                        ->title('Fehler beim Löschen')
                                        ->body('Die Datei "' . $record->file_name . '" konnte nicht gelöscht werden.')
                                        ->persistent()
                                        ->send();

                                    return null;
                                }
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
                        if (!$data['value']) {
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
                        if (!$data['value']) {
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
