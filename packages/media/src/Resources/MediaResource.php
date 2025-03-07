<?php

namespace Moox\Media\Resources;

use Filament\Forms\Form;
use Filament\Tables\Table;
use Moox\Media\Models\Media;
use Filament\Resources\Resource;
use Filament\Actions\StaticAction;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\Layout\Stack;
use Moox\Core\Traits\Base\BaseInResource;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Actions\Action;
use Moox\Media\Forms\Components\ImageDisplay;
use Moox\Media\Resources\MediaResource\Pages;
use Filament\Forms\Components\Actions\Alignment;
use Moox\Media\Tables\Columns\CustomImageColumn;
use Filament\Notifications\Notification;

class MediaResource extends Resource
{
    use BaseInResource;

    protected static ?string $model = Media::class;

    protected static ?string $navigationIcon = 'gmdi-view-timeline-o';

    protected static ?string $recordTitleAttribute = 'name';

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
                        ->content(fn($record) => $record->mime_type),

                    Placeholder::make('size')
                        ->label('Dateigröße')
                        ->content(fn($record) => number_format($record->size / 1024, 2) . ' KB'),

                    Placeholder::make('file_name')
                        ->label('Originaldateiname')
                        ->content(fn($record) => $record->file_name),

                    Placeholder::make('created_at')
                        ->label('Hochgeladen am')
                        ->content(fn($record) => $record->created_at?->format('d.m.Y H:i')),

                    Placeholder::make('updated_at')
                        ->label('Zuletzt bearbeitet')
                        ->content(fn($record) => $record->updated_at?->format('d.m.Y H:i')),

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

                ])
                ->columns(2),

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

            Actions::make([
                Action::make('delete')
                    ->label('Löschen')
                    ->color('danger')
                    ->icon('heroicon-m-trash')
                    ->requiresConfirmation()
                    ->modalHeading(fn($record) => 'Bild "' . ($record->title ?: $record->name) . '" löschen')
                    ->modalDescription('Sind Sie sicher, dass Sie dieses Bild löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.')
                    ->modalSubmitActionLabel('Ja, löschen')
                    ->modalCancelActionLabel('Abbrechen')
                    ->action(function ($record, $livewire) {
                        Media::where('id', $record->id)->delete(); // Direkt per Query löschen
                        return redirect(static::getUrl('index'));
                    }),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Stack::make([
                    CustomImageColumn::make('')
                        ->height(250)
                        ->width(350)
                        ->extraImgAttributes([
                            'class' => 'rounded-lg object-cover w-full h-full hover:scale-[1.02] transition-transform duration-200',
                        ])
                        ->tooltip(fn($record) => $record->title ?? 'Kein Titel'),
                ]),
            ])
            ->actions([
                EditAction::make()
                    ->slideOver()
                    ->extraAttributes([
                        'style' => 'visibility: hidden;',
                    ])
                    ->modalSubmitAction(false)
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->paginationPageOptions([12, 24, 48, 96])
            ->defaultSort('created_at', 'desc');
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