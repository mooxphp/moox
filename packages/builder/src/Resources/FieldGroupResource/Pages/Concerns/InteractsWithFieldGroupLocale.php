<?php

declare(strict_types=1);

namespace Moox\Builder\Resources\FieldGroupResource\Pages\Concerns;

use Filament\Actions\Action;
use Moox\Builder\Filament\Resources\Pages\Concerns\InteractsWithBuilderLocale;

trait InteractsWithFieldGroupLocale
{
    use InteractsWithBuilderLocale;

    public function hydrateInteractsWithFieldGroupLocale(): void
    {
        $this->hydrateInteractsWithBuilderLocale();
    }

    public function mountInteractsWithFieldGroupLocale(): void
    {
        $this->mountInteractsWithBuilderLocale();
    }

    protected function applyFieldGroupDefaultLocale(object $record): void
    {
        if (method_exists($record, 'setDefaultLocale') && $this->lang !== '') {
            $record->setDefaultLocale($this->lang);
        }
    }

    protected function guardFieldGroupAdminLocale(): void
    {
        $this->guardBuilderAdminLocale();
    }

    protected function getFieldGroupLanguageSelectorAction(): Action
    {
        return $this->getBuilderLanguageSelectorAction();
    }
}
