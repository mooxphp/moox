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

    protected array $processedFiles = [];

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
                        ->multiple()
                        ->maxParallelUploads(1)
                        ->acceptedFileTypes(['image/*', 'video/*', 'application/pdf'])
                        ->preserveFilenames()
                        ->maxSize(10240)
                        ->required()
                        ->afterStateUpdated(function ($state) {
                            if (! $state) {
                                return;
                            }

                            $processedFiles = session('processed_files', []);

                            foreach ($state as $key => $tempFile) {
                                if (in_array($key, $processedFiles)) {
                                    continue;
                                }

                                $model = new Media;
                                $model->exists = true;

                                $fileAdder = app(FileAdderFactory::class)->create($model, $tempFile);
                                $media = $fileAdder->preservingOriginal()->toMediaCollection('default');

                                $title = pathinfo($tempFile->getClientOriginalName(), PATHINFO_FILENAME);

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
                                $processedFiles[] = $key;
                            }

                            session(['processed_files' => $processedFiles]);
                        }),
                ])->modalSubmitAction(false),
        ];
    }
}
