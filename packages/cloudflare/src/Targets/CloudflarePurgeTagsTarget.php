<?php

declare(strict_types=1);

namespace Moox\Cloudflare\Targets;

use Moox\Cache\Data\CacheClearRequest;
use Moox\Cloudflare\CloudflareClient;
use Moox\Cloudflare\Support\AbstractCloudflareTarget;

class CloudflarePurgeTagsTarget extends AbstractCloudflareTarget
{
    public function __construct()
    {
        parent::__construct(
            key: 'cloudflare-purge-tags',
            label: __('moox-cloudflare::cloudflare.targets.purge_tags.label'),
            description: __('moox-cloudflare::cloudflare.targets.purge_tags.description'),
            icon: 'heroicon-o-tag',
        );
    }

    public static function make(): self
    {
        return new self;
    }

    protected function purge(CloudflareClient $client, CacheClearRequest $request): array
    {
        if ($request->tags === []) {
            return [
                'success' => false,
                'message' => __('moox-cloudflare::cloudflare.messages.tags_required'),
                'result' => null,
            ];
        }

        return $client->purgeTags($request->tags);
    }
}
