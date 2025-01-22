<?php

namespace Moox\Builder\Blocks\Singles;

use Moox\BuilderPro\Blocks\Singles\Publish;
use Moox\Core\Traits\Simple\SingleSimpleInResource;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\Simple\SingleSimpleInListPage;
use Moox\Core\Traits\Base\BaseInListPage;
use Moox\Core\Traits\Simple\SingleSimpleInViewPage;
use Moox\Core\Traits\Base\BaseInViewPage;
use Moox\Core\Traits\Simple\SingleSimpleInCreatePage;
use Moox\Core\Traits\Base\BaseInCreatePage;
use Moox\Core\Traits\Simple\SingleSimpleInEditPage;
use Moox\Core\Traits\Base\BaseInEditPage;
use Moox\Builder\Blocks\AbstractBlock;

class Light extends AbstractBlock
{
    public function __construct(
        string $name = 'light',
        string $label = 'Light',
        string $description = 'Shows how to disable actions in the resource',
    ) {
        parent::__construct($name, $label, $description);

        $this->incompatibleBlocks = [
            Simple::class,
            Publish::class,
            SoftDelete::class,
        ];

        $this->traits['resource'] = [
            SingleSimpleInResource::class,
            BaseInResource::class,
        ];
        $this->traits['pages']['list'] = [
            SingleSimpleInListPage::class,
            BaseInListPage::class,
        ];
        $this->traits['pages']['view'] = [
            SingleSimpleInViewPage::class,
            BaseInViewPage::class,
        ];
        $this->traits['pages']['create'] = [
            SingleSimpleInCreatePage::class,
            BaseInCreatePage::class,
        ];
        $this->traits['pages']['edit'] = [
            SingleSimpleInEditPage::class,
            BaseInEditPage::class,
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

        $this->methods['resource'] = [
            'public static function enableCreate(): bool
            {
                return false;
            }',
        ];
    }
}
