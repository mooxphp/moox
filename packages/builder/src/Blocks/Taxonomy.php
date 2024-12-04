<?php

declare(strict_types=1);

namespace Moox\Builder\Blocks;

class Taxonomy extends AbstractBlock
{
    protected array $taxonomies = [];

    protected array $formFields = [];

    public function __construct(
        string $name = 'taxonomy',
        string $label = 'Taxonomy',
        string $description = 'Simple or nested taxonomy relation',
        string $single = 'Tag',
        string $plural = 'Tags',
        string $model = '\Moox\Tag\Models\Tag::class',
        string $table = 'taggables',
        string $relationship = 'taggable',
        string $foreignKey = 'taggable_id',
        string $relatedKey = 'tag_id',
        string $createForm = '\Moox\Tag\Forms\TaxonomyCreateForm::class',
        bool $nested = false,
        bool $nullable = false,
    ) {
        parent::__construct($name, $label, $description, $nullable);

        $this->addSection('taxonomy')
            ->asMeta()
            ->hideHeader()
            ->withFields([
                'static::getTaxonomyFields()',
            ]);

        $this->taxonomies[] = [
            'name' => strtolower($single),
            'label' => $plural,
            'model' => $model,
            'table' => $table,
            'relationship' => $relationship,
            'foreign_key' => $foreignKey,
            'related_key' => $relatedKey,
            'create_form' => $createForm,
            'hierarchical' => $nested,
        ];

        $this->traits['model'] = ['Moox\Core\Traits\TaxonomyInModel'];
        $this->traits['resource'] = ['Moox\Core\Traits\TaxonomyInResource'];
        $this->traits['pages']['list'] = ['Moox\Core\Traits\TaxonomyInPages'];
        $this->traits['pages']['create'] = ['Moox\Core\Traits\TaxonomyInPages'];
        $this->traits['pages']['view'] = ['Moox\Core\Traits\TaxonomyInPages'];
        $this->traits['pages']['edit'] = ['Moox\Core\Traits\TaxonomyInPages'];

        $this->methods['model'] = [
            'protected function getResourceName(): string
            {
                return \'{{ resource_name }}\';
            }',
        ];
    }

    public function getFormFields(string $context = 'resource'): array
    {
        return $this->formFields[$context] ?? [];
    }

    public function getTaxonomies(): array
    {
        return $this->taxonomies;
    }
}
