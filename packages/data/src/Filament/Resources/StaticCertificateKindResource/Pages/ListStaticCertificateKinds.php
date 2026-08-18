<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticCertificateKindResource\Pages;

use Moox\Core\Entities\Items\Static\Pages\BaseListStatic;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Data\Filament\Resources\StaticCertificateKindResource;
use Moox\Data\Models\StaticCertificateKind;

class ListStaticCertificateKinds extends BaseListStatic
{
    use HasListPageTabs;

    protected static string $resource = StaticCertificateKindResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountTabsInListPage();
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('static-certificate-kind.tabs', StaticCertificateKind::class);
    }
}
