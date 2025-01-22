<?php

declare(strict_types=1);

namespace Moox\Builder\Blocks\Singles;

use Moox\Builder\Blocks\AbstractBlock;

class SoftDelete extends AbstractBlock
{
    public function __construct(
        string $name = 'softDelete',
        string $label = 'Soft Delete',
        string $description = 'Soft delete functionality',
    ) {
        parent::__construct($name, $label, $description);

        $this->containsBlocks = [
            'Moox\Builder\Blocks\Singles\Simple',
        ];

        $this->incompatibleBlocks = [
            'Moox\BuilderPro\Blocks\Singles\Publish',
            'Moox\Builder\Blocks\Singles\Light',
        ];

        $this->traits['model'] = [
            'Moox\Core\Traits\SoftDelete\SingleSoftDeleteInModel',
            'Moox\Core\Traits\Base\BaseInModel',
        ];
        $this->traits['resource'] = [
            'Moox\Core\Traits\SoftDelete\SingleSoftDeleteInResource',
            'Moox\Core\Traits\Base\BaseInResource',
        ];
        $this->traits['pages']['edit'] = [
            'Moox\Core\Traits\SoftDelete\SingleSoftDeleteInEditPage',
            'Moox\Core\Traits\Base\BaseInEditPage',
        ];
        $this->traits['pages']['list'] = [
            'Moox\Core\Traits\SoftDelete\SingleSoftDeleteInListPage',
            'Moox\Core\Traits\Base\BaseInListPage',
        ];
        $this->traits['pages']['view'] = [
            'Moox\Core\Traits\SoftDelete\SingleSoftDeleteInViewPage',
            'Moox\Core\Traits\Base\BaseInViewPage',
        ];
        $this->traits['pages']['create'] = [
            'Moox\Core\Traits\SoftDelete\SingleSoftDeleteInCreatePage',
            'Moox\Core\Traits\Base\BaseInCreatePage',
        ];

        $this->migrations['fields'] = [
            '$table->softDeletes()',
        ];

        $this->addSection('meta')
            ->asMeta()
            ->withFields([
                'static::getFormActions()',
            ]);

        $this->actions['resource'] = [
            '...static::getTableActions()',
        ];

        $this->actions['bulk'] = [
            '...static::getBulkActions()',
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => [
                'label' => 'trans//core::core.all',
                'icon' => 'gmdi-filter-list',
                'query' => [
                    ['field' => 'deleted_at', 'operator' => '=', 'value' => null],
                ],
            ],
            'deleted' => [
                'label' => 'trans//core::core.deleted',
                'icon' => 'gmdi-filter-list',
                'query' => [
                    ['field' => 'deleted_at', 'operator' => '!=', 'value' => null],
                ],
            ],
        ];
    }

    public function getTableInit(): array
    {
        return [
            '$currentTab = static::getCurrentTab();',
        ];
    }
}
