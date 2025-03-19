<?php

declare(strict_types=1);

namespace Moox\Localization\Filament\Tables\Columns;

use Filament\Tables\Columns\TextColumn;

class TranslationColumn extends TextColumn
{
    protected string $view = 'localization::filament.tables.columns.translations';

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->label(__('localization::fields.language'))
            ->sortable()
            ->toggleable()
            ->alignCenter()
            ->searchable();
    }
}
