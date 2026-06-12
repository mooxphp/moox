<?php

declare(strict_types=1);

namespace Moox\KositValidator\Resources\KositValidationResource\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Moox\KositValidator\Models\KositValidation;
use Moox\KositValidator\Resources\KositValidationResource;

final class ViewKositValidation extends ViewRecord
{
    protected static string $resource = KositValidationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_input_file')
                ->label(__('kosit-validator::fields.source_file'))
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->url(fn (KositValidation $record): string => route(
                    'kosit-validator.download.input-file',
                    ['validation' => $record],
                ))
                ->visible(fn (KositValidation $record): bool => $record->input_path !== null),
            Action::make('download_report_html')
                ->label(__('kosit-validator::fields.report_html'))
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->url(fn (KositValidation $record): string => route(
                    'kosit-validator.download.report-html',
                    ['validation' => $record],
                ))
                ->visible(fn (KositValidation $record): bool => $record->report_html_path !== null),
            Action::make('download_report_xml')
                ->label(__('kosit-validator::fields.report_xml'))
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->url(fn (KositValidation $record): string => route(
                    'kosit-validator.download.report-xml',
                    ['validation' => $record],
                ))
                ->visible(fn (KositValidation $record): bool => $record->report_xml_path !== null),
        ];
    }
}
