<?php

namespace Moox\Builder\Blocks;

class Simple extends AbstractBlock
{
    protected array $incompatibleBlocks = [
        SoftDelete::class,
        Publish::class,
    ];

    public function __construct(
        string $name = 'simple',
        string $label = 'Simple',
        string $description = 'Adds default actions for a simple resource',
    ) {
        parent::__construct($name, $label, $description);

        $this->useStatements['resource'] = [
            'actions' => [
                'use Filament\Tables\Actions\ViewAction;',
                'use Filament\Tables\Actions\EditAction;',
                'use Filament\Tables\Actions\DeleteBulkAction;',
            ],
        ];

        $this->traits['resource'] = ['Moox\Core\Traits\SingleSimpleInResource'];
        $this->traits['pages']['list'] = ['Moox\Core\Traits\SingleSimpleInListPage'];
        $this->traits['pages']['view'] = ['Moox\Core\Traits\SingleSimpleInViewPage'];
        $this->traits['pages']['create'] = ['Moox\Core\Traits\SingleSimpleInCreatePage'];
        $this->traits['pages']['edit'] = ['Moox\Core\Traits\SingleSimpleInEditPage'];

        $this->metaFields['resource'] = [
            'static::getSimpleFormActions()',
        ];

        $this->metaFields['pages']['list'] = [
            'static::getSimpleListActions()',
        ];

        $this->metaFields['pages']['view'] = [
            'static::getSimpleViewActions()',
        ];

        $this->metaFields['pages']['create'] = [
            'static::getSimpleCreateActions()',
        ];

        $this->metaFields['pages']['edit'] = [
            'static::getSimpleEditActions()',
        ];

        $this->actions['resource'] = [
            '\Filament\Tables\Actions\ViewAction::make()',
            '\Filament\Tables\Actions\EditAction::make()',
        ];

        $this->actions['bulk'] = [
            '\Filament\Tables\Actions\DeleteBulkAction::make()',
        ];
    }
}
