<?php

declare(strict_types=1);

namespace Moox\Builder\Services;

use Illuminate\Support\Str;
use Moox\Builder\Data\FieldGroupDefinition;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Support\FieldGroupExportSchema;
use Moox\Builder\Support\FieldRelationTree;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FieldGroupExporter
{
    /**
     * @return array{schema: string, version: int, exportedAt: string, group: array<string, mixed>}
     */
    public function export(FieldGroup $group): array
    {
        $group->load(FieldRelationTree::eagerLoadForDefinition());
        $group->load('translations');

        $definition = FieldGroupDefinition::fromModel($group);

        return [
            'schema' => FieldGroupExportSchema::SCHEMA,
            'version' => FieldGroupExportSchema::VERSION,
            'exportedAt' => now()->toIso8601String(),
            'group' => [
                ...$definition->toArray(),
                'active' => (bool) $group->active,
                'sort' => (int) $group->sort,
            ],
        ];
    }

    public function downloadResponse(FieldGroup $group): StreamedResponse
    {
        $filename = Str::slug($group->slug).'.builder-field-group.json';
        $payload = $this->export($group);

        return response()->streamDownload(
            static function () use ($payload): void {
                echo json_encode(
                    $payload,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
                );
            },
            $filename,
            ['Content-Type' => 'application/json'],
        );
    }
}
