<?php

declare(strict_types=1);

namespace Moox\Builder\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Resources\FieldGroupResource;
use Moox\Builder\Services\FieldGroupExporter;
use Moox\Builder\Services\FieldGroupImporter;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class FieldGroupDefinitionActions
{
    public static function export(?FieldGroup $record = null): Action
    {
        return Action::make('export')
            ->label(__('builder::builder.field_group.export'))
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('gray')
            ->action(function (?FieldGroup $actionRecord = null) use ($record): StreamedResponse {
                $group = $actionRecord ?? $record;

                if (! $group instanceof FieldGroup) {
                    throw new \RuntimeException('Field group record is required for export.');
                }

                return app(FieldGroupExporter::class)->downloadResponse($group);
            });
    }

    /**
     * @param  object{redirect: callable(string): void}  $livewire
     */
    public static function import(object $livewire, ?string $lang = null): Action
    {
        return Action::make('import')
            ->label(__('builder::builder.field_group.import'))
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->color('gray')
            ->modalHeading(__('builder::builder.field_group.import_heading'))
            ->modalDescription(__('builder::builder.field_group.import_description'))
            ->schema([
                FileUpload::make('file')
                    ->label(__('builder::builder.field_group.import_file'))
                    ->acceptedFileTypes(['application/json', 'text/json', 'application/octet-stream'])
                    ->maxSize(1024)
                    ->required()
                    ->live()
                    ->storeFiles(false),
                Placeholder::make('import_conflict_notice')
                    ->hiddenLabel()
                    ->content(function (callable $get): HtmlString {
                        $slug = self::slugFromUploadedFile($get('file'));

                        return new HtmlString(e(__('builder::builder.field_group.import_conflict_notice', [
                            'slug' => $slug ?? '',
                        ])));
                    })
                    ->visible(fn (callable $get): bool => self::hasImportConflict($get('file'))),
                Radio::make('resolution')
                    ->label(__('builder::builder.field_group.import_resolution'))
                    ->options(function (callable $get): array {
                        $slug = self::slugFromUploadedFile($get('file'));

                        if ($slug === null) {
                            return [];
                        }

                        $copySlug = app(FieldGroupImporter::class)->duplicateSlug($slug);

                        return [
                            'overwrite' => __('builder::builder.field_group.import_resolution_overwrite'),
                            'copy' => __('builder::builder.field_group.import_resolution_copy', [
                                'slug' => $copySlug,
                            ]),
                        ];
                    })
                    ->required(fn (callable $get): bool => self::hasImportConflict($get('file')))
                    ->visible(fn (callable $get): bool => self::hasImportConflict($get('file'))),
            ])
            ->action(function (array $data) use ($livewire, $lang): void {
                $file = $data['file'] ?? null;

                if (! $file instanceof TemporaryUploadedFile) {
                    Notification::make()
                        ->title(__('builder::builder.field_group.import_failed'))
                        ->danger()
                        ->send();

                    return;
                }

                $json = $file->get();

                if (! is_string($json) || $json === '') {
                    Notification::make()
                        ->title(__('builder::builder.field_group.import_failed'))
                        ->danger()
                        ->send();

                    return;
                }

                $importer = app(FieldGroupImporter::class);

                try {
                    $slug = $importer->slugFromJson($json);
                    $hasConflict = is_string($slug) && $importer->slugIsTaken($slug);

                    if ($hasConflict) {
                        $resolution = $data['resolution'] ?? null;

                        $group = match ($resolution) {
                            'overwrite' => $importer->importFromJson($json, replaceExisting: true),
                            'copy' => $importer->importFromJson(
                                $json,
                                slugOverride: $importer->duplicateSlug($slug),
                            ),
                            default => throw ValidationException::withMessages([
                                'resolution' => [__('builder::builder.field_group.import_resolution_required')],
                            ]),
                        };
                    } else {
                        $group = $importer->importFromJson($json);
                    }
                } catch (ValidationException $exception) {
                    $message = collect($exception->errors())->flatten()->first()
                        ?? __('builder::builder.field_group.import_failed');

                    Notification::make()
                        ->title(__('builder::builder.field_group.import_failed'))
                        ->body($message)
                        ->danger()
                        ->send();

                    throw $exception;
                }

                Notification::make()
                    ->title(__('builder::builder.field_group.import_success'))
                    ->success()
                    ->send();

                $parameters = ['record' => $group];

                if (filled($lang)) {
                    $parameters['lang'] = $lang;
                }

                $livewire->redirect(FieldGroupResource::getUrl('edit', $parameters));
            });
    }

    protected static function hasImportConflict(mixed $file): bool
    {
        $slug = self::slugFromUploadedFile($file);

        if ($slug === null) {
            return false;
        }

        return app(FieldGroupImporter::class)->slugIsTaken($slug);
    }

    protected static function slugFromUploadedFile(mixed $file): ?string
    {
        if (! $file instanceof TemporaryUploadedFile) {
            return null;
        }

        $json = $file->get();

        if (! is_string($json) || $json === '') {
            return null;
        }

        return app(FieldGroupImporter::class)->slugFromJson($json);
    }
}
