<?php

declare(strict_types=1);

namespace Moox\BlockEditor\EntityQuery\Data;

use Carbon\CarbonInterface;

final readonly class FeedItem
{
    /**
     * @param  list<string>  $categories
     * @param  array<string, mixed>  $image
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        public int|string $id,
        public string $title,
        public string $slug,
        public string $permalink,
        public string $description,
        public string $excerpt,
        public string $descriptionPlain,
        public string $excerptPlain,
        public ?CarbonInterface $publishedAt,
        public array $image,
        public ?string $imageUrl,
        public ?string $authorName,
        public array $categories,
        public array $extra = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_merge([
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'permalink' => $this->permalink,
            'description' => $this->description,
            'excerpt' => $this->excerpt,
            'description_plain' => $this->descriptionPlain,
            'excerpt_plain' => $this->excerptPlain,
            'published_at' => $this->publishedAt,
            'image' => $this->image,
            'image_url' => $this->imageUrl,
            'author_name' => $this->authorName,
            'categories' => $this->categories,
        ], $this->extra);
    }
}
