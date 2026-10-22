<?php

declare(strict_types=1);

namespace Moox\EBilling\Resources\InvoiceResource\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Moox\EBilling\Actions\CreateManualUploadBatchAction;
use Moox\EBilling\Actions\CreateManualUploadDocumentAction;
use Moox\EBilling\Support\DocumentClassification;
use Moox\EBilling\Support\DocumentClassificationLabels;
use Moox\EBilling\Support\FieldValidationProfile;

/**
 * The manual upload header actions of an e-billing list page: the single upload and the bulk upload
 * wizard, both configured per resource under `e-billing.resources.{key}.manual_upload`.
 */
final class ManualUploadActions
{
    /**
     * @param  list<string>  $selectableTypes
     */
    private static function manualUploadRecipientEmailField(array $selectableTypes): TextInput
    {
        return TextInput::make('recipient_email')
            ->label(__('e-billing::fields.buyer_email'))
            ->helperText(__('e-billing::fields.hint_info_buyer_email'))
            ->email()
            ->maxLength(255)
            ->live(onBlur: true)
            ->required(function (Get $get) use ($selectableTypes): bool {
                if (count($selectableTypes) === 1) {
                    $documentType = $selectableTypes[0];
                } else {
                    $declared = $get('document_type');
                    $documentType = is_int($declared) || is_string($declared) ? (string) $declared : '';
                }

                return $documentType !== ''
                    && FieldValidationProfile::priority('buyer_email', $documentType) === 'must';
            });
    }

    /**
     * Declared document type for the manual upload (ADR 0011 addendum): a collapsible "which type?"
     * instruction and a choice without preselection, required unless it is only a bulk default.
     * Nothing to choose with fewer than two codes.
     *
     * @param  list<string>  $selectableTypes
     * @return list<Section|Select>
     */
    private static function manualUploadDocumentTypeComponents(array $selectableTypes, bool $required = true): array
    {
        if (count($selectableTypes) < 2) {
            return [];
        }

        return [
            Section::make(__('e-billing::fields.document_classification_help.title'))
                ->collapsible()
                ->collapsed()
                ->schema([
                    Text::make(self::documentTypeInstructionHtml(
                        DocumentClassificationLabels::instruction($selectableTypes),
                    )),
                ]),
            self::manualUploadDocumentTypeSelect($selectableTypes)
                ->helperText(__($required
                    ? 'e-billing::fields.document_classification_help.helper'
                    : 'e-billing::fields.bulk_upload_default_helper'))
                ->required($required),
        ];
    }

    /**
     * @param  list<string>  $selectableTypes
     */
    private static function manualUploadDocumentTypeSelect(array $selectableTypes): Select
    {
        return Select::make('document_type')
            ->label(__('e-billing::fields.document_type'))
            ->options(array_intersect_key(DocumentClassificationLabels::options(), array_flip($selectableTypes)))
            ->placeholder(__('e-billing::fields.document_classification_help.placeholder'));
    }

    /**
     * One compact block per code: bold label, rule, bulleted examples. Inline styles, because the panel
     * theme does not ship utility classes for package markup; every text is escaped.
     *
     * @param  list<array{label: string, rule: ?string, examples: list<string>}>  $blocks
     */
    private static function documentTypeInstructionHtml(array $blocks): HtmlString
    {
        $html = '';

        foreach ($blocks as $index => $block) {
            $html .= $index > 0 ? '<div style="margin-top: 1rem;">' : '<div>';
            $html .= '<p style="margin: 0; font-weight: 600;">'.e($block['label']).'</p>';

            if ($block['rule'] !== null) {
                $html .= '<p style="margin: 0.25rem 0 0;">'.e($block['rule']).'</p>';
            }

            if ($block['examples'] !== []) {
                $html .= '<ul style="margin: 0.375rem 0 0; padding-inline-start: 1.25rem; list-style: disc outside;">';
                foreach ($block['examples'] as $example) {
                    $html .= '<li style="margin: 0.125rem 0 0;">'.e($example).'</li>';
                }
                $html .= '</ul>';
            }

            $html .= '</div>';
        }

        return new HtmlString($html);
    }

    /**
     * Settings both manual upload actions share, or null when the resource has no manual upload.
     *
     * @return array{
     *     config: array<string, mixed>,
     *     disk: string,
     *     directory: string,
     *     max_size_kb: int,
     *     scope: string,
     *     requires_letterhead_overlay: bool,
     *     resource: string,
     *     selectable_types: list<string>
     * }|null
     */
    private static function manualUploadSettings(string $resourceKey): ?array
    {
        $config = config('e-billing.resources.'.$resourceKey.'.manual_upload');

        if (! is_array($config) || ! ($config['enabled'] ?? false)) {
            return null;
        }

        return [
            'config' => $config,
            'disk' => (string) config('e-billing.manual_upload.source_disk', 'local'),
            'directory' => (string) config('e-billing.manual_upload.source_path', 'ebilling/manual-uploads/source'),
            'max_size_kb' => max(1, (int) config('e-billing.manual_upload.max_size_kb', 20480)),
            'scope' => is_string($config['scope'] ?? null) ? $config['scope'] : $resourceKey,
            'requires_letterhead_overlay' => (bool) ($config['requires_letterhead_overlay'] ?? false),
            'resource' => $resourceKey,
            'selectable_types' => DocumentClassification::selectableForUpload($resourceKey),
        ];
    }

    /**
     * The PDF field of a manual upload; each action adds how it names and counts its files.
     *
     * @param  array{disk: string, directory: string, max_size_kb: int}  $settings
     * @param  array<string, string>  $helperReplace
     */
    private static function manualUploadPdfField(
        string $name,
        string $label,
        string $helperKey,
        array $settings,
        array $helperReplace = [],
    ): FileUpload
    {
        return FileUpload::make($name)
            ->label($label)
            ->helperText(__($helperKey, [
                'max_mb' => (string) max(1, (int) round($settings['max_size_kb'] / 1024)),
                ...$helperReplace,
            ]))
            ->acceptedFileTypes(['application/pdf'])
            ->maxSize($settings['max_size_kb'])
            ->disk($settings['disk'])
            ->directory($settings['directory'])
            ->required();
    }

    /**
     * Document attributes that come from the resource and the uploader, not from the file.
     *
     * @param  array{disk: string, scope: string, requires_letterhead_overlay: bool, resource: string}  $settings
     * @return array{
     *     source_pdf_disk: string,
     *     scope: string,
     *     resource: string,
     *     requires_letterhead_overlay: bool,
     *     uploader_user_id: ?string
     * }
     */
    private static function manualUploadSharedAttributes(array $settings): array
    {
        return [
            'source_pdf_disk' => $settings['disk'],
            'scope' => $settings['scope'],
            'resource' => $settings['resource'],
            'requires_letterhead_overlay' => $settings['requires_letterhead_overlay'],
            'uploader_user_id' => Auth::id() !== null ? (string) Auth::id() : null,
        ];
    }

    public static function make(string $resourceKey): ?Action
    {
        $settings = self::manualUploadSettings($resourceKey);

        if ($settings === null) {
            return null;
        }

        $label = is_string($settings['config']['label'] ?? null)
            ? $settings['config']['label']
            : __('e-billing::fields.action_manual_upload');

        return Action::make('manualUpload')
            ->label($label)
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->modalHeading($label)
            ->modalDescription(__('e-billing::fields.action_manual_upload_modal_description'))
            ->modalSubmitActionLabel(__('e-billing::fields.action_manual_upload_submit'))
            ->form([
                self::manualUploadPdfField(
                    'pdf',
                    __('e-billing::fields.manual_upload_pdf'),
                    'e-billing::fields.manual_upload_pdf_helper',
                    $settings,
                )
                    ->storeFileNamesIn('pdf_original_filename'),
                ...self::manualUploadDocumentTypeComponents($settings['selectable_types']),
                self::manualUploadRecipientEmailField($settings['selectable_types']),
            ])
            ->action(function (array $data, $livewire) use ($settings): void {
                $path = $data['pdf'] ?? null;

                if (! is_string($path) || $path === '') {
                    return;
                }

                app(CreateManualUploadDocumentAction::class)->execute([
                    ...self::manualUploadSharedAttributes($settings),
                    'source_pdf_path' => $path,
                    'original_filename' => self::resolveUploadedOriginalFilename($data, $path),
                    'document_type' => $data['document_type'] ?? null,
                    'recipient_email' => $data['recipient_email'] ?? null,
                ]);

                Notification::make()
                    ->title(__('e-billing::fields.notification_manual_upload_success_title'))
                    ->body(__('e-billing::fields.notification_manual_upload_success_body'))
                    ->success()
                    ->send();

                self::pollIdenticalDuplicateToast($livewire);
            });
    }

    /**
     * Bulk upload (`manual_upload.bulk_enabled`): a two-step wizard. Step 1 takes the files and optional
     * batch defaults; step 2 shows one row per file, prefilled from the defaults, with the same per-file
     * rules as the single upload. Each file becomes its own document; a failing file is skipped and reported.
     */
    public static function makeBulk(string $resourceKey): ?Action
    {
        $settings = self::manualUploadSettings($resourceKey);

        if ($settings === null || ! ($settings['config']['bulk_enabled'] ?? false)) {
            return null;
        }

        $directory = $settings['directory'];
        $maxFiles = max(1, (int) config('e-billing.manual_upload.bulk_max_files', 50));
        $selectableTypes = $settings['selectable_types'];
        $choosesType = count($selectableTypes) >= 2;

        return Action::make('bulkManualUpload')
            ->label(__('e-billing::fields.action_bulk_manual_upload'))
            ->icon(Heroicon::OutlinedRectangleStack)
            ->modalHeading(__('e-billing::fields.action_bulk_manual_upload'))
            ->modalDescription(__('e-billing::fields.action_bulk_manual_upload_modal_description'))
            ->modalSubmitActionLabel(__('e-billing::fields.action_manual_upload_submit'))
            ->modalWidth('5xl')
            ->steps([
                Step::make(__('e-billing::fields.bulk_upload_step_files'))
                    ->schema([
                        self::manualUploadPdfField(
                            'pdfs',
                            __('e-billing::fields.bulk_upload_pdfs'),
                            'e-billing::fields.bulk_upload_pdfs_helper',
                            $settings,
                            ['max_files' => (string) $maxFiles],
                        )
                            ->multiple()
                            ->maxFiles($maxFiles)
                            ->getUploadedFileNameForStorageUsing(
                                fn (TemporaryUploadedFile $file): string => self::bulkStoredFileName(
                                    $file->getFilename(),
                                ),
                            )
                            ->storeFileNamesIn('pdf_original_filenames'),
                        ...self::manualUploadDocumentTypeComponents($selectableTypes, required: false),
                        TextInput::make('recipient_email')
                            ->label(__('e-billing::fields.buyer_email'))
                            ->helperText(__('e-billing::fields.bulk_upload_default_helper'))
                            ->email()
                            ->maxLength(255),
                        Hidden::make('applied_defaults')->dehydrated(false),
                    ])
                    ->afterValidation(function (Get $get, Set $set) use ($directory): void {
                        $defaults = [
                            'document_type' => $get('document_type'),
                            'recipient_email' => $get('recipient_email'),
                        ];

                        $set(
                            'files',
                            self::mergeBulkUploadRows(
                                self::bulkUploadFiles($get('pdfs'), $directory),
                                (array) ($get('files') ?? []),
                                (array) ($get('applied_defaults') ?? []),
                                $defaults,
                            ),
                        );
                        $set('applied_defaults', $defaults);
                    }),
                Step::make(__('e-billing::fields.bulk_upload_step_details'))
                    ->schema([
                        Repeater::make('files')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make(__('e-billing::fields.manual_upload_pdf')),
                                ...($choosesType
                                    ? [TableColumn::make(__('e-billing::fields.document_type'))->markAsRequired()]
                                    : []),
                                TableColumn::make(__('e-billing::fields.buyer_email')),
                            ])
                            ->schema([
                                Hidden::make('source_pdf_path'),
                                TextInput::make('filename')->disabled()->dehydrated(false),
                                ...($choosesType
                                    ? [self::manualUploadDocumentTypeSelect($selectableTypes)->required()->live()]
                                    : []),
                                self::manualUploadRecipientEmailField($selectableTypes)->helperText(null),
                            ])
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false),
                    ]),
            ])
            ->action(function (array $data, $livewire) use ($settings): void {
                $storedPaths = array_values(array_filter((array) ($data['pdfs'] ?? []), 'is_string'));
                $originalFilenames = array_filter((array) ($data['pdf_original_filenames'] ?? []), 'is_string');

                try {
                    $result = app(CreateManualUploadBatchAction::class)->execute(
                        $storedPaths,
                        $originalFilenames,
                        array_values((array) ($data['files'] ?? [])),
                        self::manualUploadSharedAttributes($settings),
                    );
                } catch (InvalidArgumentException $exception) {
                    Notification::make()
                        ->title(__('e-billing::fields.notification_bulk_upload_title'))
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                $body = __('e-billing::fields.notification_bulk_upload_body', [
                    'created' => (string) count($result['created']),
                    'failed' => (string) count($result['failed']),
                ]);

                foreach ($result['failed'] as $failure) {
                    $body .= "\n".$failure['filename'].': '.$failure['reason'];
                }

                $notification = Notification::make()
                    ->title(__('e-billing::fields.notification_bulk_upload_title'))
                    ->body($body);

                if ($result['failed'] === []) {
                    $notification->success();
                } else {
                    $notification->warning()->persistent();
                }

                $notification->send();

                if ($result['created'] !== []) {
                    self::pollIdenticalDuplicateToast($livewire);
                }
            });
    }

    /**
     * Stored name of a bulk-uploaded file, derived from Livewire's unique temporary file name so that step 2
     * knows each file's final path before it is stored. Contains no client-controlled characters.
     */
    public static function bulkStoredFileName(string $temporaryFilename): string
    {
        return hash('sha256', $temporaryFilename).'.pdf';
    }

    /**
     * The stored path and client name of each file uploaded in step 1.
     *
     * @return list<array{source_pdf_path: string, filename: string}>
     */
    private static function bulkUploadFiles(mixed $files, string $directory): array
    {
        $uploads = [];

        foreach ((array) $files as $file) {
            if (! $file instanceof TemporaryUploadedFile) {
                continue;
            }

            $uploads[] = [
                'source_pdf_path' => rtrim($directory, '/').'/'.self::bulkStoredFileName($file->getFilename()),
                'filename' => $file->getClientOriginalName(),
            ];
        }

        return $uploads;
    }

    /**
     * Step-2 rows after leaving step 1: one row per file. A file already listed keeps its row, and a field
     * there takes the new batch default only while it still holds the previously applied one, so edits
     * survive going back. A new file gets the defaults; a removed file loses its row.
     *
     * @param  list<array{source_pdf_path: string, filename: string}>  $files
     * @param  array<array-key, mixed>  $existingRows
     * @param  array<string, mixed>  $previousDefaults
     * @param  array{document_type: mixed, recipient_email: mixed}  $defaults
     * @return array<array-key, array<string, mixed>>
     */
    public static function mergeBulkUploadRows(
        array $files,
        array $existingRows,
        array $previousDefaults,
        array $defaults,
    ): array
    {
        $existingByPath = [];

        foreach ($existingRows as $key => $row) {
            if (is_array($row) && is_string($row['source_pdf_path'] ?? null)) {
                $existingByPath[$row['source_pdf_path']] = [$key, $row];
            }
        }

        $rows = [];

        foreach ($files as $file) {
            [$key, $row] = $existingByPath[$file['source_pdf_path']] ?? [(string) Str::uuid(), null];

            if ($row === null) {
                $rows[$key] = [...$file, ...$defaults];

                continue;
            }

            foreach ($defaults as $field => $value) {
                if ((string) ($row[$field] ?? '') === (string) ($previousDefaults[$field] ?? '')) {
                    $row[$field] = $value;
                }
            }

            $rows[$key] = $row;
        }

        return $rows;
    }

    private static function pollIdenticalDuplicateToast(mixed $livewire): void
    {
        if (! is_object($livewire) || ! method_exists($livewire, 'checkIdenticalDuplicateToast')) {
            return;
        }

        $livewire->js(<<<'JS'
            let n = 0;
            const timer = setInterval(() => {
                $wire.checkIdenticalDuplicateToast();
                if (++n >= 10) {
                    clearInterval(timer);
                }
            }, 2000);
        JS);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function resolveUploadedOriginalFilename(array $data, string $storedPath): string
    {
        $names = $data['pdf_original_filename'] ?? null;

        if (is_array($names)) {
            $candidate = $names[$storedPath] ?? null;

            if (is_string($candidate) && $candidate !== '') {
                return $candidate;
            }
        }

        if (is_string($names) && $names !== '') {
            return $names;
        }

        return basename($storedPath);
    }
}
