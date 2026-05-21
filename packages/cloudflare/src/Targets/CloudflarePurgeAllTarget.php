<?php

declare(strict_types=1);

namespace Moox\Cloudflare\Targets;

use Moox\Cache\Data\CacheClearRequest;
use Moox\Cloudflare\CloudflareClient;
use Moox\Cloudflare\Support\AbstractCloudflareTarget;

class CloudflarePurgeAllTarget extends AbstractCloudflareTarget
{
    public function __construct()
    {
        parent::__construct(
            key: 'cloudflare-purge-all',
            label: __('moox-cloudflare::cloudflare.targets.purge_all.label'),
            description: __('moox-cloudflare::cloudflare.targets.purge_all.description'),
            color: 'danger',
        );
    }

    public static function make(): self
    {
        return new self;
    }

    protected function purge(CloudflareClient $client, CacheClearRequest $request): array
    {
        return $client->purgeEverything();
    }
}
