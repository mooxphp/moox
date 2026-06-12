<?php

declare(strict_types=1);

namespace Moox\Press\Resources\WpSiteResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Moox\Press\Models\WpSite;
use Moox\Press\Models\WpSiteMeta;
use Moox\Press\Resources\WpSiteResource;
use Override;

class EditWpSite extends EditRecord
{
    protected static string $resource = WpSiteResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    #[Override]
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $site = WpSite::with('siteMeta')->find($data['id']);

        if ($site instanceof WpSite) {
            foreach ($site->siteMeta as $meta) {
                /** @var WpSiteMeta $meta */
                $data[$meta->meta_key] = $meta->meta_value;
            }
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if (! $this->record instanceof WpSite) {
            return;
        }

        $metaFields = config('press.default_site_meta', []);

        foreach (array_keys($metaFields) as $metaKey) {
            if (array_key_exists($metaKey, $this->data)) {
                $this->record->addOrUpdateMeta($metaKey, $this->data[$metaKey]);
            }
        }
    }
}
