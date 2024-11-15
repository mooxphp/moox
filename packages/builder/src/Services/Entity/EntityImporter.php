<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Entity;

use Illuminate\Database\Schema\Blueprint;
use Moox\Builder\Blocks\AbstractBlock;
use Moox\Builder\Blocks\FileUpload;
use Moox\Builder\Blocks\Image;
use Moox\Builder\Blocks\MarkdownEditor;
use Moox\Builder\Blocks\MultiSelect;
use Moox\Builder\Blocks\Radio;
use Moox\Builder\Blocks\Relationship;
use Moox\Builder\Blocks\Text;
use Moox\Builder\Services\Migration\MigrationAnalyzer;
use Moox\Builder\Services\Migration\MigrationFinder;
use Moox\Builder\Types\AbstractType;
use Moox\Builder\Types\ArrayType;
use Moox\Builder\Types\EnumType;
use Moox\Builder\Types\FileType;
use Moox\Builder\Types\ImageType;
use Moox\Builder\Types\TextType;

class EntityImporter
{
    public function __construct(
        private readonly MigrationAnalyzer $analyzer,
        private readonly MigrationFinder $finder
    ) {}

    public function importFromTableName(string $tableName): ?array
    {
        $migrationPath = $this->finder->findMigrationForTable($tableName);
        if (! $migrationPath) {
            return null;
        }

        $blueprint = $this->finder->extractBlueprintFromFile($migrationPath);
        if (! $blueprint) {
            return null;
        }

        return $this->importFromBlueprint($blueprint);
    }

    public function importFromBlueprint(Blueprint $blueprint): array
    {
        $analysis = $this->analyzer->analyzeBlueprint($blueprint);
        $blocks = [];

        foreach ($analysis['columns'] as $name => $type) {
            $blocks[] = $this->createBlockFromType($name, $type);
        }

        foreach ($analysis['relationships'] as $relation) {
            $blocks[] = new Relationship(
                name: $relation['name'],
                label: ucfirst($relation['name']),
                description: '',
                nullable: $relation['nullable'],
                relatedModel: ucfirst($relation['related_table']),
                multiple: $relation['multiple']
            );
        }

        return $blocks;
    }

    private function createBlockFromType(string $name, AbstractType $type): AbstractBlock
    {
        $label = ucfirst(str_replace('_', ' ', $name));

        return match (get_class($type)) {
            FileType::class => new FileUpload($name, $label, ''),
            ImageType::class => new Image($name, $label, ''),
            EnumType::class => new Radio($name, $label, ''),
            ArrayType::class => $this->createArrayBlock($name, $label),
            TextType::class => new MarkdownEditor($name, $label, ''),
            default => new Text($name, $label, '')
        };
    }

    private function createArrayBlock(string $name, string $label): AbstractBlock
    {
        return new MultiSelect($name, $label, '');
    }
}
