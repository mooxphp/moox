<?php

declare(strict_types=1);

namespace Moox\KositValidator\DTOs;

class KositResult
{
    public function __construct(
        public readonly int $exitCode,
        public readonly string $stdout,
        public readonly string $stderr,
        public readonly ?string $reportXmlPath,
        public readonly ?string $reportHtmlPath,
        public readonly ?string $xmlPath = null,
    ) {
    }

    public function passed(): bool
    {
        return $this->exitCode === 0;
    }

    public function failed(): bool
    {
        return ! $this->passed();
    }

    /**
     * Parse the report XML to extract all validation messages with severity.
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

        $messages = [];
        $xml = @simplexml_load_file($this->reportXmlPath);

        if ($xml === false) {
            return $this->failed()
                ? [['type' => 'error', 'text' => __('kosit-validator::fields.could_not_parse_report_xml'), 'location' => null, 'rule' => null]]
                : [];
        }

        $xml->registerXPathNamespace('rep', 'http://www.xoev.de/de/validator/varl/1');
        $xml->registerXPathNamespace('s', 'http://purl.oclc.org/dml/schematron');
        $xml->registerXPathNamespace('svrl', 'http://purl.oclc.org/dml/schematron');

        $repMessages = $xml->xpath('//rep:message') ?: [];
        foreach ($repMessages as $msg) {
            $text = trim((string) $msg);
            if ($text === '') {
                continue;
            }

            $level = strtolower((string) ($msg['level'] ?? 'error'));
            $type = match (true) {
                str_contains($level, 'info') => 'info',
                str_contains($level, 'warn') => 'warning',
                default => 'error',
            };

            $location = (string) ($msg['xpathLocation'] ?? '');
            $rule = (string) ($msg['code'] ?? '');
            $messages[] = [
                'type' => $type,
                'text' => $text,
                'location' => $location !== '' ? $location : null,
                'rule' => $rule !== '' ? $rule : null,
            ];
        }

        if ($messages === []) {
            $failedAsserts = $xml->xpath('//s:failed-assert | //svrl:failed-assert') ?: [];
            foreach ($failedAsserts as $assert) {
                $text = self::schematronMessageText($assert);
                if ($text !== '') {
                    $location = (string) ($assert['location'] ?? '');
                    $rule = (string) ($assert['id'] ?? '');
                    $messages[] = [
                        'type' => 'error',
                        'text' => $text,
                        'location' => $location !== '' ? $location : null,
                        'rule' => $rule !== '' ? $rule : null,
                    ];
                }
            }

            $successfulReports = $xml->xpath('//s:successful-report | //svrl:successful-report') ?: [];
            foreach ($successfulReports as $report) {
                $text = self::schematronMessageText($report);
                if ($text !== '') {
                    $role = strtolower((string) ($report['role'] ?? 'warning'));
                    $type = match (true) {
                        str_contains($role, 'info') => 'info',
                        str_contains($role, 'warn') => 'warning',
                        default => 'warning',
                    };
                    $location = (string) ($report['location'] ?? '');
                    $rule = (string) ($report['id'] ?? '');
                    $messages[] = [
                        'type' => $type,
                        'text' => $text,
                        'location' => $location !== '' ? $location : null,
                        'rule' => $rule !== '' ? $rule : null,
                    ];
                }
            }
        }

        return $messages;
    }

    /**
     * Backward-compatible: returns flat array of error message strings only.
     *
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

    /**
     * SVRL / Schematron output often uses a namespaced {@code text} child element.
     */
    private static function schematronMessageText(\SimpleXMLElement $element): string
    {
        $ns = 'http://purl.oclc.org/dml/schematron';
        $childText = $element->children($ns)->text;
        if ($childText !== null) {
            $trimmed = trim((string) $childText);
            if ($trimmed !== '') {
                return $trimmed;
            }
        }

        return trim((string) $element);
    }
}
