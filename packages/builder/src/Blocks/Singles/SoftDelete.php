<?php

declare(strict_types=1);

namespace Moox\Builder\Blocks\Singles;

use Moox\Builder\Blocks\AbstractBlock;

class SoftDelete extends AbstractBlock
{
    protected array $containsBlocks = [
        Simple::class,
    ];

    protected array $incompatibleBlocks = [
        Publish::class,
    ];

    public function __construct(
        string $name = 'softDelete',
        string $label = 'Soft Delete',
        string $description = 'Soft delete functionality',
    ) {
        parent::__construct($name, $label, $description);

        $this->useStatements = [
            'resource' => [
                'actions' => [
                    'use Filament\Tables\Actions\DeleteBulkAction;',
                    'use Filament\Tables\Actions\RestoreBulkAction;',
                ],
            ],
            'pages' => [
                'edit' => ['use Filament\Actions\Action;'],
                'view' => ['use Filament\Actions\Action;'],
            ],
        ];

        $this->traits['model'] = ['Moox\Core\Traits\SoftDelete\SingleSoftDeleteInModel'];
        $this->traits['resource'] = ['Moox\Core\Traits\SoftDelete\SingleSoftDeleteInResource'];
        $this->traits['pages']['edit'] = ['Moox\Core\Traits\SoftDelete\SingleSoftDeleteInEditPage'];
        $this->traits['pages']['list'] = ['Moox\Core\Traits\SoftDelete\SingleSoftDeleteInListPage'];
        $this->traits['pages']['view'] = ['Moox\Core\Traits\SoftDelete\SingleSoftDeleteInViewPage'];
        $this->traits['pages']['create'] = ['Moox\Core\Traits\SoftDelete\SingleSoftDeleteInCreatePage'];

        $this->actions['pages']['edit']['header'] = [
            "Action::make('restore')
                ->label(__('core::core.restore'))
                ->color('success')
                ->button()
                ->extraAttributes(['class' => 'w-full'])
                ->action(fn (\$record) => \$record->restore())
                ->visible(fn (\$livewire, \$record) => \$record && \$record->trashed())",
            "Action::make('delete')
                ->label(__('core::core.delete'))
                ->color('danger')
                ->button()
                ->extraAttributes(['class' => 'w-full'])
                ->action(fn (\$record) => \$record->delete())
                ->visible(fn (\$livewire, \$record) => \$record && ! \$record->trashed())",
        ];

        $this->actions['bulk'] = [
            "DeleteBulkAction::make()
                ->hidden(fn () => request()->routeIs('*.trash'))",
            "RestoreBulkAction::make()
                ->visible(fn () => request()->routeIs('*.trash'))",
        ];

        $this->migrations['fields'] = [
            '$table->softDeletes()',
        ];

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
