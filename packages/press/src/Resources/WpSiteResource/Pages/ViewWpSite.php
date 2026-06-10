<?php

declare(strict_types=1);

namespace Moox\Press\Resources\WpSiteResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Press\Models\WpSite;
use Moox\Press\Models\WpSiteMeta;
use Moox\Press\Resources\WpSiteResource;
use Override;

class ViewWpSite extends ViewRecord
{
    protected static string $resource = WpSiteResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), DeleteAction::make()];
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
}
