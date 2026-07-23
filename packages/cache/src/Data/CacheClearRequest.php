<?php

declare(strict_types=1);

namespace Moox\Cache\Data;

final readonly class CacheClearRequest
{
    /**
     * @param  list<string>  $urls
     * @param  list<string>  $tags
     * @param  list<string>  $hosts
     */
    public function __construct(
        public ?string $key = null,
        public ?string $store = null,
        public ?string $slug = null,
        public bool $recursive = false,
        public array $urls = [],
        public array $tags = [],
        public array $hosts = [],
    ) {
    }
}
