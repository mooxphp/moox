<?php

declare(strict_types=1);

namespace Moox\BlockEditor\EntityQuery\Mapping\Relations;

final class FeedItemRelationDefinition
{
    /**
     * @param  list<string>  $attributes
     */
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly string $output,
        public readonly string $labelAttribute = 'title',
        public readonly array $attributes = ['name', 'title'],
        public readonly string $path = '',
        public readonly bool $resolveUrl = false,
        public readonly ?string $eagerLoad = null,
    ) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromConfig(string $name, array $config): ?self
    {
        $type = $config['type'] ?? null;

        if (! is_string($type) || trim($type) === '') {
            return null;
        }

        $output = $config['output'] ?? null;

        if (! is_string($output) || trim($output) === '') {
            return null;
        }

        $attributes = $config['attributes'] ?? ['name', 'title'];

        if (! is_array($attributes)) {
            $attributes = ['name', 'title'];
        }

        $attributes = array_values(array_filter(
            $attributes,
            static fn (mixed $attribute): bool => is_string($attribute) && trim($attribute) !== ''
        ));

        $labelAttribute = is_string($config['label_attribute'] ?? null) && $config['label_attribute'] !== ''
            ? $config['label_attribute']
            : 'title';

        $path = is_string($config['path'] ?? null) ? $config['path'] : '';

        $eagerLoad = $config['eager_load'] ?? null;

        return new self(
            name: $name,
            type: $type,
            output: $output,
            labelAttribute: $labelAttribute,
            attributes: $attributes !== [] ? $attributes : ['name', 'title'],
            path: $path,
            resolveUrl: ($config['resolve_url'] ?? false) === true,
            eagerLoad: is_string($eagerLoad) && $eagerLoad !== '' ? $eagerLoad : null,
        );
    }
}
