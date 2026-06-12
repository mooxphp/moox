<?php

declare(strict_types=1);

namespace Moox\Press\Resources\WpSiteResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Press\Models\WpSite;
use Moox\Press\Resources\WpSiteResource;

class CreateWpSite extends CreateRecord
{
    protected static string $resource = WpSiteResource::class;

    public function afterCreate(): void
    {
        if (! $this->record instanceof WpSite) {
            return;
        }

        $metaFields = config('press.default_site_meta', []);

        foreach ($metaFields as $metaKey => $defaultValue) {
            $this->record->addOrUpdateMeta($metaKey, $this->data[$metaKey] ?? $defaultValue);
        }
    }
}
