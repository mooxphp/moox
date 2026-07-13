<?php

declare(strict_types=1);

namespace Moox\BlockEditor\EntityQuery\Mapping;

use Illuminate\Support\Str;
use Moox\BlockEditor\EntityQuery\Mapping\Relations\FeedItemRelationDefinition;

final class FeedItemMapping
{
    /**
     * @param  array<string, FeedItemRelationDefinition>  $relations
     * @param  list<string>  $fallbackTitleFrom
     * @param  array<string, string>  $translationFields
     * @param  array<string, string>  $extra
     */
    public function __construct(
        public readonly array $relations,
        public readonly array $fallbackTitleFrom,
        public readonly string $untitledLabel,
        public readonly array $translationFields,
        public readonly array $extra,
    ) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromConfig(array $config): self
    {
        $defaults = config('moox-editor.dynamic_feed.mapping_defaults', []);

        if (! is_array($defaults)) {
            $defaults = [];
        }

        /** @var array<string, mixed> $merged */
        $merged = array_replace_recursive($defaults, $config);

        $relations = self::normalizeRelations($merged);

        $fallbackTitleFrom = self::normalizeStringList($merged['fallback_title_from'] ?? ['excerpt', 'description']);

        $translationFields = is_array($merged['translation_fields'] ?? null)
            ? self::normalizeStringMap($merged['translation_fields'])
            : [];

        $extra = is_array($merged['extra'] ?? null)
            ? self::normalizeStringMap($merged['extra'])
            : [];

        return new self(
            relations: $relations,
            fallbackTitleFrom: $fallbackTitleFrom !== [] ? $fallbackTitleFrom : ['excerpt', 'description'],
            untitledLabel: self::resolveLabel($merged['untitled_label'] ?? null),
            translationFields: $translationFields,
            extra: $extra,
        );
    }

    /**
     * @return list<string>
     */
    public function eagerLoadPaths(): array
    {
        $paths = [];

        foreach ($this->relations as $relation) {
            if ($relation->eagerLoad !== null) {
                $paths[] = $relation->eagerLoad;
            }
        }

        return array_values(array_unique($paths));
    }

    /**
     * @param  array<string, mixed>  $merged
     * @return array<string, FeedItemRelationDefinition>
     */
    private static function normalizeRelations(array $merged): array
    {
        $relationsConfig = $merged['relations'] ?? [];

        if (! is_array($relationsConfig) || $relationsConfig === []) {
            $relationsConfig = self::legacyRelationsConfig($merged);
        }

        $relations = [];

        foreach ($relationsConfig as $name => $config) {
            if (! is_string($name) || $name === '' || ! is_array($config)) {
                continue;
            }

            $definition = FeedItemRelationDefinition::fromConfig($name, $config);

            if ($definition !== null) {
                $relations[$name] = $definition;
            }
        }

        return $relations;
    }

    /**
     * @param  array<string, mixed>  $merged
     * @return array<string, array<string, mixed>>
     */
    private static function legacyRelationsConfig(array $merged): array
    {
        $relations = [];

        if (array_key_exists('taxonomy', $merged) && is_string($merged['taxonomy']) && $merged['taxonomy'] !== '') {
            $relations[$merged['taxonomy']] = [
                'type' => 'taxonomy',
                'output' => 'categories',
                'label_attribute' => 'title',
                'eager_load' => $merged['taxonomy'].'.translations',
            ];
        }

        $authorRelation = is_string($merged['author_relation'] ?? null) && $merged['author_relation'] !== ''
            ? $merged['author_relation']
            : null;

        if ($authorRelation !== null) {
            $relations[$authorRelation] = [
                'type' => 'translation_relation',
                'output' => 'author_name',
                'attributes' => self::normalizeStringList($merged['author_attributes'] ?? ['name', 'title']),
                'eager_load' => 'translations.'.$authorRelation,
            ];
        }

        $imageAttribute = is_string($merged['image_attribute'] ?? null) && $merged['image_attribute'] !== ''
            ? $merged['image_attribute']
            : null;

        if ($imageAttribute !== null) {
            $relations[$imageAttribute] = [
                'type' => 'attribute',
                'path' => $imageAttribute,
                'output' => 'image',
                'resolve_url' => true,
            ];
        }

        return $relations;
    }

    /**
     * @return array<string, string>
     */
    public function resolvedTranslationFields(): array
    {
        $defaults = [
            'title' => 'title',
            'slug' => 'slug',
            'permalink' => 'permalink',
            'description' => 'description',
            'excerpt' => 'excerpt',
            'published_at' => 'published_at',
        ];

        return array_merge($defaults, $this->translationFields);
    }

    private static function resolveLabel(mixed $label): string
    {
        if (! is_string($label) || trim($label) === '') {
            $label = config('moox-editor.dynamic_feed.untitled_label', 'Untitled');
        }

        if (! is_string($label) || trim($label) === '') {
            return 'Untitled';
        }

        if (str_starts_with($label, 'trans//')) {
            return (string) __(Str::after($label, 'trans//'));
        }

        return $label;
    }

    /**
     * @return list<string>
     */
    private static function normalizeStringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            $value,
            static fn (mixed $item): bool => is_string($item) && trim($item) !== ''
        ));
    }

    /**
     * @return array<string, string>
     */
    private static function normalizeStringMap(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $normalized = [];

        foreach ($value as $key => $item) {
            if (! is_string($key) || trim($key) === '') {
                continue;
            }

            if (! is_string($item) || trim($item) === '') {
                continue;
            }

            $normalized[$key] = $item;
        }

        return $normalized;
    }
}
