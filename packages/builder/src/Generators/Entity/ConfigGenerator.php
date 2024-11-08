<?php

declare(strict_types=1);

namespace Moox\Builder\Generators\Entity;

class ConfigGenerator extends AbstractGenerator
{
    protected array $tabs = [];

    protected array $taxonomies = [];

    protected array $relations = [];

    public function generate(): void
    {
        $this->collectFeatures();

        $configContent = $this->generateConfigContent();
        $configPath = $this->getConfigPath();

        $this->writeFile($configPath, $configContent);
        $this->formatGeneratedFiles();
    }

    protected function collectFeatures(): void
    {
        foreach ($this->getBlocks() as $block) {
            if (method_exists($block, 'getTabs')) {
                $this->tabs = array_merge($this->tabs, $block->getTabs());
            }

            if (method_exists($block, 'getTaxonomies')) {
                $this->taxonomies = array_merge($this->taxonomies, $block->getTaxonomies());
            }

            if (method_exists($block, 'getRelations')) {
                $this->relations = array_merge($this->relations, $block->getRelations());
            }
        }
    }

    protected function generateConfigContent(): string
    {
        $template = $this->loadStub($this->getTemplate());

        $variables = [
            'namespace' => $this->context->getNamespace('config'),
            'entity' => $this->context->getEntityName(),
            'entity_plural' => $this->context->getPluralModelName(),
            'tabs' => $this->generateTabsConfig(),
            'taxonomies' => $this->generateTaxonomiesConfig(),
            'relations' => $this->generateRelationsConfig(),
        ];

        return $this->replaceTemplateVariables($template, $variables);
    }

    protected function generateTabsConfig(): string
    {
        $tabsConfig = [];

        foreach ($this->tabs as $tabName) {
            $tabStub = $this->loadStub(__DIR__."/../../Templates/Entity/tabs/{$tabName}.tab.stub");
            $tabsConfig[] = $tabStub;
        }

        return implode("\n", $tabsConfig);
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

    protected function getConfigPath(): string
    {
        $basePath = match ($this->context->getContextType()) {
            'app' => config_path('entities'),
            'preview' => config_path('previews'),
            'package' => $this->context->getPath('config').'/entities',
            default => throw new \InvalidArgumentException('Invalid context type: '.$this->context->getContextType()),
        };

        return $basePath.'/'.$this->context->getEntityName().'.php';
    }

    protected function getGeneratorType(): string
    {
        return 'config';
    }
}
