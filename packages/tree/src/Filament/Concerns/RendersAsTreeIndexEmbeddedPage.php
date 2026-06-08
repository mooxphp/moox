<?php

declare(strict_types=1);

namespace Moox\Tree\Filament\Concerns;

use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;

trait RendersAsTreeIndexEmbeddedPage
{
    abstract protected function isEmbeddedInTreeIndex(): bool;

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return false;
    }

    /**
     * @param  mixed  $url
     */
    public function redirect($url, $navigate = false): void
    {
        if ($this->isEmbeddedInTreeIndex()) {
            return;
        }

        parent::redirect($url, $navigate);
    }

    public function getView(): string
    {
        return 'filament-tree-index::filament.pages.tree-index-inspector';
    }

    public function render(): View
    {
        return view($this->getView(), $this->getViewData());
    }

    public function getTitle(): string
    {
        return '';
    }

    public function getHeading(): string
    {
        return '';
    }

    public function getSubheading(): ?string
    {
        return null;
    }

    /**
     * @return array<Action>
     */
    public function getHeaderActions(): array
    {
        return [];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->getFormContentComponent(),
        ]);
    }
}
