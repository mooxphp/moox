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

        $this->traits['resource'] = ['Moox\Core\Traits\Simple\SingleSimpleInResource'];
        $this->traits['pages']['list'] = ['Moox\Core\Traits\Simple\SingleSimpleInListPage'];
        $this->traits['pages']['view'] = ['Moox\Core\Traits\Simple\SingleSimpleInViewPage'];
        $this->traits['pages']['create'] = ['Moox\Core\Traits\Simple\SingleSimpleInCreatePage'];
        $this->traits['pages']['edit'] = ['Moox\Core\Traits\Simple\SingleSimpleInEditPage'];

        $this->addSection('meta')
            ->asMeta()
            ->withFields([
                'static::getSimpleFormActions()',
            ]);

        $this->actions['resource'] = [
            '\Filament\Tables\Actions\ViewAction::make()',
            '\Filament\Tables\Actions\EditAction::make()',
        ];

        $this->actions['bulk'] = [
            '\Filament\Tables\Actions\DeleteBulkAction::make()',
        ];
    }
}
