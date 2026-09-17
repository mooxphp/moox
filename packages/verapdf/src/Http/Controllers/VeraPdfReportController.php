<?php

declare(strict_types=1);

namespace Moox\VeraPdf\Http\Controllers;

use Moox\VeraPdf\Models\VeraPdfValidation;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class VeraPdfReportController
{
    public function html(VeraPdfValidation $validation): BinaryFileResponse
    {
        $htmlPath = $validation->report_html_path;

        if ($htmlPath === null || ! is_file($htmlPath)) {
            abort(404, __('verapdf::fields.no_report_available'));
        }

        return response()->file($htmlPath, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline' 'self'; img-src data:; sandbox allow-same-origin",
        ]);
    }

    public function downloadInputFile(VeraPdfValidation $validation): BinaryFileResponse
    {
        return $this->downloadFromAbsolutePath(
            $validation->input_path,
            'application/pdf',
        );
    }

    public function downloadReportXml(VeraPdfValidation $validation): BinaryFileResponse
    {
        return $this->downloadFromAbsolutePath(
            $validation->report_xml_path,
            'application/xml',
        );
    }

    public function downloadReportHtml(VeraPdfValidation $validation): BinaryFileResponse
    {
        return $this->downloadFromAbsolutePath(
            $validation->report_html_path,
            'text/html; charset=UTF-8',
        );
    }

    private function downloadFromAbsolutePath(?string $absolutePath, string $contentType): BinaryFileResponse
    {
        if ($absolutePath === null || ! is_file($absolutePath)) {
            abort(404);
        }

        return response()
            ->download($absolutePath, basename($absolutePath), [
                'Content-Type' => $contentType,
                'Content-Disposition' => 'attachment; filename="'.basename($absolutePath).'"',
                'X-Content-Type-Options' => 'nosniff',
            ]);
    }
}
