<?php

declare(strict_types=1);

namespace Moox\Tree\Tests\Support;

use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Moox\Tree\Filament\Concerns\InteractsWithResourceTreeIndex;

class TestTreeIndexHost extends Component implements HasSchemas
{
    use InteractsWithResourceTreeIndex;
    use InteractsWithSchemas;

    public string $treeIndexConfigurationKey = '';

    public string $lang = 'en';

    public ?int $treeSelectedId = null;

    public string $tableSearch = '';

    public function mount(
        string $treeIndexConfigurationKey = '',
        string $lang = 'en',
        ?int $treeSelectedId = null,
        string $tableSearch = '',
    ): void {
        $this->treeIndexConfigurationKey = $treeIndexConfigurationKey;
        $this->lang = $lang;
        $this->treeSelectedId = $treeSelectedId;
        $this->tableSearch = $tableSearch;

        $this->mountInteractsWithResourceTreeIndex();
    }

    public function hydrate(): void
    {
        $this->hydrateInteractsWithResourceTreeIndex();
    }

    public function render(): View
    {
        return view('filament-tree-index::filament.pages.tree-index-content', $this->getTreeIndexViewData());
    }

    /**
     * @return class-string
     */
    protected function getInspectorResourceClass(): string
    {
        return $this->configuration()->getSourceResourceClass() ?? TestForwardTreeResource::class;
    }
}
