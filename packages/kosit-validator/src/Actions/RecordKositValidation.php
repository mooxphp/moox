<?php

declare(strict_types=1);

namespace Moox\KositValidator\Actions;

use Moox\KositValidator\DTOs\KositResult;
use Moox\KositValidator\Models\KositValidation;

final class RecordKositValidation
{
    public function __invoke(KositResult $result): KositValidation
    {
        return KositValidation::create([
            'input_path' => $result->xmlPath,
            'report_xml_path' => $result->reportXmlPath,
            'report_html_path' => $result->reportHtmlPath,
            'passed' => $result->passed(),
            'errors' => $result->validationMessages(),
            'validated_at' => now(),
        ]);
    }
}
