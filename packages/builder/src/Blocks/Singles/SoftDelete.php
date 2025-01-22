<?php

declare(strict_types=1);

namespace Moox\Builder\Blocks\Singles;

use Moox\BuilderPro\Blocks\Singles\Publish;
use Moox\Core\Traits\SoftDelete\SingleSoftDeleteInModel;
use Moox\Core\Traits\Base\BaseInModel;
use Moox\Core\Traits\SoftDelete\SingleSoftDeleteInResource;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\SoftDelete\SingleSoftDeleteInEditPage;
use Moox\Core\Traits\Base\BaseInEditPage;
use Moox\Core\Traits\SoftDelete\SingleSoftDeleteInListPage;
use Moox\Core\Traits\Base\BaseInListPage;
use Moox\Core\Traits\SoftDelete\SingleSoftDeleteInViewPage;
use Moox\Core\Traits\Base\BaseInViewPage;
use Moox\Core\Traits\SoftDelete\SingleSoftDeleteInCreatePage;
use Moox\Core\Traits\Base\BaseInCreatePage;
use Override;
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
            Simple::class,
        ];

        $this->incompatibleBlocks = [
            Publish::class,
            Light::class,
        ];

        $this->traits['model'] = [
            SingleSoftDeleteInModel::class,
            BaseInModel::class,
        ];
        $this->traits['resource'] = [
            SingleSoftDeleteInResource::class,
            BaseInResource::class,
        ];
        $this->traits['pages']['edit'] = [
            SingleSoftDeleteInEditPage::class,
            BaseInEditPage::class,
        ];
        $this->traits['pages']['list'] = [
            SingleSoftDeleteInListPage::class,
            BaseInListPage::class,
        ];
        $this->traits['pages']['view'] = [
            SingleSoftDeleteInViewPage::class,
            BaseInViewPage::class,
        ];
        $this->traits['pages']['create'] = [
            SingleSoftDeleteInCreatePage::class,
            BaseInCreatePage::class,
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

    #[Override]
    public function getTableInit(): array
    {
        return [
            '$currentTab = static::getCurrentTab();',
        ];
    }
}
