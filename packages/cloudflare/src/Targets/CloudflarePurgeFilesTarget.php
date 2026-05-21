<?php

declare(strict_types=1);

namespace Moox\Cloudflare\Targets;

use Moox\Cache\Data\CacheClearRequest;
use Moox\Cloudflare\CloudflareClient;
use Moox\Cloudflare\Support\AbstractCloudflareTarget;

class CloudflarePurgeFilesTarget extends AbstractCloudflareTarget
{
    public function __construct()
    {
        parent::__construct(
            key: 'cloudflare-purge-files',
            label: __('moox-cloudflare::cloudflare.targets.purge_files.label'),
            description: __('moox-cloudflare::cloudflare.targets.purge_files.description'),
            icon: 'heroicon-o-link',
            color: 'warning',
        );
    }

    public static function make(): self
    {
        return new self;
    }

    protected function purge(CloudflareClient $client, CacheClearRequest $request): array
    {
        if ($request->urls === []) {
            return [
                'success' => false,
                'message' => __('moox-cloudflare::cloudflare.messages.urls_required'),
                'result' => null,
            ];
        }

        return $client->purgeFiles($request->urls);
    }
}
