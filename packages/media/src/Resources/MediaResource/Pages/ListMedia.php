<?php

namespace Moox\Media\Resources\MediaResource\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use Moox\Media\Models\Media;
use Moox\Media\Resources\MediaResource;
use Spatie\MediaLibrary\MediaCollections\FileAdderFactory;

class ListMedia extends ListRecords
{
    protected static string $resource = MediaResource::class;

    public function getHeaderActions(): array
    {
        return [
            Action::make('upload')
                ->label('Datei hochladen')
                ->form([
                    FileUpload::make('file')
                        ->label('Datei auswählen')
                        ->image()
                        ->imageEditor()
                        ->acceptedFileTypes(['image/*', 'video/*', 'application/pdf'])
                        ->preserveFilenames()
                        ->maxSize(10240)
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state) {
                            if (! $state) {
                                return;
                            }

                            $model = new Media;
                            $model->exists = true;

                            $fileAdder = app(FileAdderFactory::class)->create($model, $state);
                            $media = $fileAdder->toMediaCollection('default');

                            $title = pathinfo($state->getClientOriginalName(), PATHINFO_FILENAME);

                            $media->title = $title;
                            $media->alt = $title;
                            $media->original_model_type = Media::class;
                            $media->original_model_id = $media->id;
                            $media->model_id = $media->id;
                            $media->model_type = Media::class;

                            if (str_starts_with($media->mime_type, 'image/')) {
                                [$width, $height] = getimagesize($media->getPath());
                                $media->setCustomProperty('dimensions', [
                                    'width' => $width,
                                    'height' => $height,
                                ]);
                            }

                            $media->save();
                        }),
                ])->modalSubmitAction(false),
        ];
    }
}
