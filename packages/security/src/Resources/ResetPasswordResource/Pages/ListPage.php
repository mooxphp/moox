<?php

namespace Moox\Security\Resources\ResetPasswordResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Security\Models\ResetPassword;
use Moox\Security\Resources\ResetPasswordResource;
use Moox\Security\Resources\ResetPasswordResource\Widgets\ResetPasswordWidgets;
use Override;

class ListPage extends ListRecords
{
    use HasListPageTabs;

    public static string $resource = ResetPasswordResource::class;

    protected function getActions(): array
    {
        return [];
    }

    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [
            ResetPasswordWidgets::class,
        ];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('security.resources.reset_password.tabs', ResetPassword::class);
    }
}
