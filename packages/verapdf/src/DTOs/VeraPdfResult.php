<?php

declare(strict_types=1);

namespace Moox\VeraPdf\DTOs;

class VeraPdfResult
{
    public function __construct(
        public readonly int $exitCode,
        public readonly string $stdout,
        public readonly string $stderr,
        public readonly ?string $reportXmlPath,
        public readonly ?string $reportHtmlPath,
        public readonly ?string $pdfPath = null,
    ) {
    }

    public function passed(): bool
    {
        if ($this->reportXmlPath && file_exists($this->reportXmlPath)) {
            $compliant = $this->isCompliantFromReport();

            if ($compliant !== null) {
                return $compliant;
            }
        }

        return $this->exitCode === 0;
    }

    public function failed(): bool
    {
        return ! $this->passed();
    }

    /**
     * Parse the report XML to extract validation messages with severity.
     *
     * @return list<array{type: string, text: string, location: string|null, rule: string|null}>
     */
    public function validationMessages(): array
    {
        if (! $this->reportXmlPath || ! file_exists($this->reportXmlPath)) {
            $text = trim($this->stderr ?: $this->stdout);

            return $this->failed() && $text !== ''
                ? [['type' => 'error', 'text' => $text, 'location' => null, 'rule' => null]]
                : [];
        }

        $xml = @simplexml_load_file($this->reportXmlPath);

        if ($xml === false) {
            return $this->failed()
                ? [['type' => 'error', 'text' => __('verapdf::fields.could_not_parse_report_xml'), 'location' => null, 'rule' => null]]
                : [];
        }

        $messages = [];
        $failedRules = $xml->xpath('//rule[@status="failed"]') ?: [];

        foreach ($failedRules as $rule) {
            $description = trim((string) ($rule->description ?? ''));
            if ($description === '') {
                $description = trim((string) $rule);
            }
            if ($description === '') {
                continue;
            }

            $clause = trim((string) ($rule['clause'] ?? ''));
            $testNumber = trim((string) ($rule['testNumber'] ?? ''));
            $specification = trim((string) ($rule['specification'] ?? ''));

            $ruleId = match (true) {
                $clause !== '' && $testNumber !== '' => $clause.'#'.$testNumber,
                $clause !== '' => $clause,
                $specification !== '' => $specification,
                default => null,
            };

            $location = null;
            $checks = $rule->xpath('./check[@status="failed"]') ?: [];
            if ($checks !== []) {
                $context = trim((string) ($checks[0]->context ?? ''));
                $location = $context !== '' ? $context : null;
            }

            $messages[] = [
                'type' => 'error',
                'text' => $description,
                'location' => $location,
                'rule' => $ruleId,
            ];
        }

        if ($messages === [] && $this->failed()) {
            $statement = '';
            $reports = $xml->xpath('//validationReport') ?: [];
            if ($reports !== []) {
                $statement = trim((string) ($reports[0]['statement'] ?? ''));
            }
            if ($statement !== '') {
                $messages[] = [
                    'type' => 'error',
                    'text' => $statement,
                    'location' => null,
                    'rule' => null,
                ];
            }
        }

        return $messages;
    }

    /**
     * @return list<string>
     */
    public function errors(): array
    {
        return array_map(
            fn (array $m): string => $m['text'],
            array_values(array_filter(
                $this->validationMessages(),
                fn (array $m): bool => $m['type'] === 'error'
            ))
        );
    }

    private function isCompliantFromReport(): ?bool
    {
        $xml = @simplexml_load_file($this->reportXmlPath);

        if ($xml === false) {
            return null;
        }

        $reports = $xml->xpath('//validationReport') ?: [];

        if ($reports === []) {
            return null;
        }

        $value = strtolower((string) ($reports[0]['isCompliant'] ?? ''));

        return match ($value) {
            'true' => true,
            'false' => false,
            default => null,
        };
    }
}
