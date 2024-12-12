<?php

declare(strict_types=1);

namespace Moox\Builder\Generators\Entity;

use Illuminate\Support\Str;
use Moox\Builder\Contexts\BuildContext;
use Moox\Builder\Services\File\FileManager;

class ConfigGenerator extends AbstractGenerator
{
    protected array $tabs = [];

    protected array $taxonomies = [];

    protected array $relations = [];

    public function __construct(
        BuildContext $context,
        FileManager $fileManager,
        array $blocks = []
    ) {
        parent::__construct($context, $fileManager, $blocks);
    }

    public function generate(): void
    {
        $this->collectFeatures();

        $configContent = $this->generateConfigContent();
        $configPath = $this->getConfigPath();

        $this->writeFile($configPath, $configContent);
    }

    protected function collectFeatures(): void
    {
        $this->tabs = [];
        $this->taxonomies = [];
        $this->relations = [];

        foreach ($this->getBlocks() as $block) {
            if (method_exists($block, 'getTabs')) {
                $this->tabs = $block->getTabs();
            }

            if (method_exists($block, 'getTaxonomies')) {
                $this->taxonomies = array_merge($this->taxonomies, $block->getTaxonomies());
            }

            if (method_exists($block, 'getRelations')) {
                $this->relations = array_merge($this->relations, $block->getRelations());
            }
        }
    }

    protected function formatTabs(): string
    {
        if (empty($this->tabs)) {
            return '[]';
        }

        $output = "[\n";
        foreach ($this->tabs as $key => $tab) {
            $output .= "        '$key' => [\n";
            foreach ($tab as $property => $value) {
                if (is_array($value)) {
                    $output .= "            '$property' => [\n";
                    foreach ($value as $queryItem) {
                        $output .= "                [\n";
                        foreach ($queryItem as $field => $fieldValue) {
                            $formattedValue = is_null($fieldValue) ? 'null' : "'$fieldValue'";
                            $output .= "                    '$field' => $formattedValue,\n";
                        }
                        $output .= "                ],\n";
                    }
                    $output .= "            ],\n";
                } else {
                    $output .= "            '$property' => '$value',\n";
                }
            }
            $output .= "        ],\n";
        }
        $output .= '    ]';

        return $output;
    }

    protected function generateConfigContent(): string
    {
        $entityName = Str::kebab($this->context->getEntityName());
        $contextType = $this->context->getContextType();
        $translationKey = match ($contextType) {
            'app' => "entities/{$entityName}",
            'preview' => "previews/{$entityName}",
            'package' => $this->context->getConfig()['package']['name']."/{$entityName}",
            default => throw new \InvalidArgumentException('Invalid context type: '.$contextType),
        };

        $taxonomies = $this->generateTaxonomiesConfig();
        $taxonomiesConfig = empty($taxonomies) ? '[]' : "[\n        {$taxonomies}\n    ]";

        return "<?php\n\nreturn [\n".
            "    'single' => 'trans//{$translationKey}.{$this->getSingularKey()}',\n".
            "    'plural' => 'trans//{$translationKey}.{$this->getPluralKey()}',\n".
            "    'tabs' => {$this->formatTabs()},\n".
            "    'relations' => [],\n".
            "    'taxonomies' => {$taxonomiesConfig},\n".
            "];\n";
    }

    protected function getConfigPath(): string
    {
        return $this->context->getPath('config').'/'.Str::kebab($this->context->getEntityName()).'.php';
    }

    protected function getSingularKey(): string
    {
        return Str::kebab($this->context->getEntityName());
    }

    protected function getPluralKey(): string
    {
        return Str::kebab($this->context->getPluralName());
    }

    protected function generateTaxonomiesConfig(): string
    {
        if (empty($this->taxonomies)) {
            return '';
        }

        $taxonomyStub = $this->loadStub(__DIR__.'/../../Templates/Entity/taxonomy.part.stub');
        $taxonomiesConfig = [];

        foreach ($this->taxonomies as $taxonomy) {
            $variables = [
                'TaxonomyName' => $taxonomy['name'],
                'TaxonomyLabel' => $taxonomy['label'],
                'TaxonomyModel' => $taxonomy['model'],
                'TaxonomyTable' => $taxonomy['table'],
                'TaxonomyRelationship' => $taxonomy['relationship'],
                'TaxonomyForeignKey' => $taxonomy['foreign_key'],
                'TaxonomyRelatedKey' => $taxonomy['related_key'],
                'TaxonomyCreateForm' => $taxonomy['create_form'],
                'TaxonomyHierarchical' => $taxonomy['hierarchical'] ? 'true' : 'false',
            ];

            $taxonomiesConfig[] = $this->replaceTemplateVariables($taxonomyStub, $variables);
        }

        return implode("\n", $taxonomiesConfig);
    }

    protected function generateRelationsConfig(): string
    {
        if (empty($this->relations)) {
            return '';
        }

        $relationStub = $this->loadStub(__DIR__.'/../../Templates/Entity/relation.part.stub');
        $relationsConfig = [];

        foreach ($this->relations as $relation) {
            $variables = [
                'RelationName' => $relation['name'],
                'RelationLabel' => $relation['label'],
                'RelationModel' => $relation['model'],
                'RelationTable' => $relation['table'],
                'RelationRelationship' => $relation['relationship'],
                'RelationForeignKey' => $relation['foreign_key'],
                'RelationRelatedKey' => $relation['related_key'],
                'RelationHierarchical' => $relation['hierarchical'] ? 'true' : 'false',
            ];

            $relationsConfig[] = $this->replaceTemplateVariables($relationStub, $variables);
        }

        return implode("\n", $relationsConfig);
    }

    protected function getGeneratorType(): string
    {
        return 'config';
    }
}
