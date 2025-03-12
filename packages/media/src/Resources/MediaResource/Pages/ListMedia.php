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
                        ->multiple(config('media.upload.resource.multiple'))
                        ->disk(config('media.upload.resource.disk'))
                        ->directory(config('media.upload.resource.directory'))
                        ->visibility(config('media.upload.resource.visibility'))
                        ->maxSize(config('media.upload.resource.max_file_size'))
                        ->minSize(config('media.upload.resource.min_file_size'))
                        ->maxFiles(config('media.upload.resource.max_files'))
                        ->minFiles(config('media.upload.resource.min_files'))
                        ->acceptedFileTypes(config('media.upload.resource.accepted_file_types'))
                        ->imageResizeMode(config('media.upload.resource.image_resize_mode'))
                        ->imageCropAspectRatio(config('media.upload.resource.image_crop_aspect_ratio'))
                        ->imageResizeTargetWidth(config('media.upload.resource.image_resize_target_width'))
                        ->imageResizeTargetHeight(config('media.upload.resource.image_resize_target_height'))
                        ->imageEditor(config('media.upload.resource.image_editor.enabled'))
                        ->imageEditorAspectRatios(config('media.upload.resource.image_editor.aspect_ratios'))
                        ->imageEditorViewportWidth(config('media.upload.resource.image_editor.viewport_width'))
                        ->imageEditorViewportHeight(config('media.upload.resource.image_editor.viewport_height'))
                        ->imageEditorMode(config('media.upload.resource.image_editor.mode'))
                        ->imageEditorEmptyFillColor(config('media.upload.resource.image_editor.empty_fill_color'))
                        ->panelLayout(config('media.upload.resource.panel_layout'))
                        ->downloadable(config('media.upload.resource.show_download_button'))
                        ->openable(config('media.upload.resource.show_open_button'))
                        ->previewable(config('media.upload.resource.show_preview'))
                        ->reorderable(config('media.upload.resource.reorderable'))
                        ->appendFiles(config('media.upload.resource.append_files'))
                        ->afterStateUpdated(function ($state) {
                            if (!$state) {
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
