<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

/**
 * Translatable labels, "when to choose" hints and the upload instruction for the document classification
 * choice (ADR 0011). Keys: `e-billing::fields.document_classification.{code}.label|hint|rule|examples` and
 * `e-billing::fields.document_classification_help.*`; hosts override them by publishing the package
 * translations. Codes without a label fall back to the code-list label.
 */
final class DocumentClassificationLabels
{
    public static function label(string $documentType): string
    {
        $key = "e-billing::fields.document_classification.{$documentType}.label";
        $label = __($key);

        return is_string($label) && $label !== $key ? $label : DocumentTypeCodeResolver::labelForCode($documentType);
    }

    public static function hint(string $documentType): ?string
    {
        $key = "e-billing::fields.document_classification.{$documentType}.hint";
        $hint = __($key);

        return is_string($hint) && $hint !== $key ? $hint : null;
    }

    /**
     * Dropdown options for the configured classification types.
     *
     * @return array<string, string> document type code => label
     */
    public static function options(): array
    {
        $options = [];

        foreach (array_keys(DocumentClassification::signs()) as $documentType) {
            // Numeric type codes come back as int array keys.
            $options[(string) $documentType] = self::label((string) $documentType);
        }

        return $options;
    }

    /**
     * Content of the collapsible upload instruction, per code: label, rule and examples. Plain strings;
     * the resource renders them with schema components.
     *
     * @param  list<string>  $documentTypes
     * @return list<array{label: string, rule: ?string, examples: list<string>}>
     */
    public static function instruction(array $documentTypes): array
    {
        $blocks = [];

        foreach ($documentTypes as $documentType) {
            $rule = self::translated("e-billing::fields.document_classification.{$documentType}.rule");
            $examples = self::translated("e-billing::fields.document_classification.{$documentType}.examples");

            $blocks[] = [
                'label' => self::label($documentType),
                'rule' => is_string($rule) ? $rule : null,
                'examples' => is_array($examples) ? array_values(array_filter($examples, is_string(...))) : [],
            ];
        }

        return $blocks;
    }

    /**
     * @return string|array<mixed>|null
     */
    private static function translated(string $key): string|array|null
    {
        $value = __($key);

        return $value === $key ? null : $value;
    }
}
