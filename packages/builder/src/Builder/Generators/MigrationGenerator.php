<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Generators;

class MigrationGenerator
{
    protected string $table;

    protected array $baseFields = [];

    /** @var array<Feature> */
    protected array $features = [];

    protected array $additionalFields = [];

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function addBaseField(string $field): self
    {
        $this->baseFields[] = $field;

        return $this;
    }

    public function addFeature(Feature $feature): self
    {
        $this->features[] = $feature;

        return $this;
    }

    public function addAdditionalField(string $field): self
    {
        $this->additionalFields[] = $field;

        return $this;
    }

    protected function getFeatureFields(): array
    {
        $fields = [];
        foreach ($this->features as $feature) {
            $fields = array_merge($fields, $feature->getMigrations());
        }

        return $fields;
    }

    public function generate(): string
    {
        $template = file_get_contents(__DIR__.'/../Templates/migration.php.stub');

        $replacements = [
            '{{ table }}' => $this->table,
            '{{ base_fields }}' => implode("\n            ", $this->baseFields),
            '{{ feature_fields }}' => implode("\n            ", $this->getFeatureFields()),
            '{{ additional_fields }}' => implode("\n            ", $this->additionalFields),
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );
    }
}
