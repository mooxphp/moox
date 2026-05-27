<?php

declare(strict_types=1);

namespace Moox\KositValidator\Http\Controllers;

use Moox\KositValidator\Models\KositValidation;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class KositReportController
{
    public function html(KositValidation $validation): BinaryFileResponse
    {
        $htmlPath = $validation->report_html_path;

        if ($htmlPath === null || ! is_file($htmlPath)) {
            abort(404, 'KOSIT report not found');
        }

        return response()->file($htmlPath, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline' 'self'; img-src data:; sandbox allow-same-origin",
        ]);
    }

    public function downloadInputFile(KositValidation $validation): BinaryFileResponse
    {
        return $this->downloadFromAbsolutePath(
            $validation->input_path,
            'application/xml',
        );
    }

    public function downloadReportXml(KositValidation $validation): BinaryFileResponse
    {
        return $this->downloadFromAbsolutePath(
            $validation->report_xml_path,
            'application/xml',
        );
    }

    public function downloadReportHtml(KositValidation $validation): BinaryFileResponse
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
