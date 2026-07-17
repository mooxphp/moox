<?php

declare(strict_types=1);

namespace Moox\VeraPdf\Actions;

use Moox\VeraPdf\DTOs\VeraPdfResult;
use Moox\VeraPdf\Models\VeraPdfValidation;

final class RecordVeraPdfValidation
{
    public function __invoke(VeraPdfResult $result): VeraPdfValidation
    {
        return VeraPdfValidation::create([
            'input_path' => $result->pdfPath,
            'report_xml_path' => $result->reportXmlPath,
            'report_html_path' => $result->reportHtmlPath,
            'passed' => $result->passed(),
            'errors' => $result->validationMessages(),
            'validated_at' => now(),
        ]);
    }
}
