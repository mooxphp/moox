<?php

declare(strict_types=1);

namespace App\Builder\Resources\StaticLanguageResource\Pages;

use App\Builder\Models\StaticLanguage;
use App\Builder\Resources\StaticLanguageResource;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Base\BaseInListPage;
use Moox\Core\Traits\Simple\SingleSimpleInListPage;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Override;

class ListStaticLanguages extends ListRecords
{
    use BaseInListPage;
    use HasListPageTabs;
    use SingleSimpleInListPage;

    protected static string $resource = StaticLanguageResource::class;

    #[Override]
    public function mount(): void
    {
        parent::mount();
        $this->mountTabsInListPage();
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('previews.static-language.tabs', StaticLanguage::class);
    }
}
