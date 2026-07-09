<?php

declare(strict_types=1);

namespace Moox\BlockEditor\EntityQuery;

final class EntityQueryDefinition
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        public readonly string $sourceKey,
        public readonly int $limit,
        public readonly string $orderBy,
        public readonly string $orderDirection,
        public readonly array $filters,
        public readonly string $locale,
    ) {}

    /**
     * @param  array<string, mixed>  $block
     */
    public static function fromBlock(array $block, string $locale): self
    {
        return self::fromArray([
            'sourceKey' => $block['sourceKey'] ?? '',
            'limit' => $block['limit'] ?? config('moox-editor.dynamic_feed.default_limit', 5),
            'orderBy' => $block['orderBy'] ?? config('moox-editor.dynamic_feed.default_order_by', 'published_at'),
            'orderDirection' => $block['orderDirection'] ?? config('moox-editor.dynamic_feed.default_order_direction', 'desc'),
            'filters' => $block['filters'] ?? [],
        ], $locale);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, string $locale): self
    {
        $maxLimit = (int) config('moox-editor.dynamic_feed.max_limit', 50);
        $limit = (int) ($data['limit'] ?? config('moox-editor.dynamic_feed.default_limit', 5));

        return new self(
            sourceKey: (string) ($data['sourceKey'] ?? ''),
            limit: max(1, min($maxLimit, $limit)),
            orderBy: (string) ($data['orderBy'] ?? config('moox-editor.dynamic_feed.default_order_by', 'published_at')),
            orderDirection: strtolower((string) ($data['orderDirection'] ?? 'desc')) === 'asc' ? 'asc' : 'desc',
            filters: is_array($data['filters'] ?? null) ? $data['filters'] : [],
            locale: $locale,
        );
    }
}
