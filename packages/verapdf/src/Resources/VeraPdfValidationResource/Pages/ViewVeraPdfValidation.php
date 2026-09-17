<?php

declare(strict_types=1);

namespace Moox\VeraPdf\Resources\VeraPdfValidationResource\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Moox\VeraPdf\Models\VeraPdfValidation;
use Moox\VeraPdf\Resources\VeraPdfValidationResource;

final class ViewVeraPdfValidation extends ViewRecord
{
    protected static string $resource = VeraPdfValidationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_input_file')
                ->label(__('verapdf::fields.source_file'))
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->url(fn (VeraPdfValidation $record): string => route(
                    'verapdf.download.input-file',
                    ['validation' => $record],
                ))
                ->visible(fn (VeraPdfValidation $record): bool => is_string($record->input_path) && is_file($record->input_path)),
            Action::make('download_report_html')
                ->label(__('verapdf::fields.report_html'))
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->url(fn (VeraPdfValidation $record): string => route(
                    'verapdf.download.report-html',
                    ['validation' => $record],
                ))
                ->visible(fn (VeraPdfValidation $record): bool => is_string($record->report_html_path) && is_file($record->report_html_path)),
            Action::make('download_report_xml')
                ->label(__('verapdf::fields.report_xml'))
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->url(fn (VeraPdfValidation $record): string => route(
                    'verapdf.download.report-xml',
                    ['validation' => $record],
                ))
                ->visible(fn (VeraPdfValidation $record): bool => is_string($record->report_xml_path) && is_file($record->report_xml_path)),
        ];
    }
}
