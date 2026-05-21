<?php

declare(strict_types=1);

namespace Moox\Cloudflare\Targets;

use Moox\Cache\Data\CacheClearRequest;
use Moox\Cloudflare\CloudflareClient;
use Moox\Cloudflare\Support\AbstractCloudflareTarget;

class CloudflarePurgeHostsTarget extends AbstractCloudflareTarget
{
    public function __construct()
    {
        parent::__construct(
            key: 'cloudflare-purge-hosts',
            label: __('moox-cloudflare::cloudflare.targets.purge_hosts.label'),
            description: __('moox-cloudflare::cloudflare.targets.purge_hosts.description'),
            icon: 'heroicon-o-globe-alt',
        );
    }

    public static function make(): self
    {
        return new self;
    }

    protected function purge(CloudflareClient $client, CacheClearRequest $request): array
    {
        if ($request->hosts === []) {
            return [
                'success' => false,
                'message' => __('moox-cloudflare::cloudflare.messages.hosts_required'),
                'result' => null,
            ];
        }

        return $client->purgeHosts($request->hosts);
    }
}
