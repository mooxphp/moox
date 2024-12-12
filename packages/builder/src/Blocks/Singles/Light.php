<?php

namespace Moox\Builder\Blocks\Singles;

use Moox\Builder\Blocks\AbstractBlock;

class Light extends AbstractBlock
{
    protected array $incompatibleBlocks = [
        Simple::class,
        Publish::class,
        SoftDelete::class,
    ];

    public function __construct(
        string $name = 'light',
        string $label = 'Light',
        string $description = 'Shows how to disable actions in the resource',
    ) {
        parent::__construct($name, $label, $description);

        $this->traits['resource'] = [
            'Moox\Core\Traits\Simple\SingleSimpleInResource',
            'Moox\Core\Traits\Base\BaseInResource',
        ];
        $this->traits['pages']['list'] = [
            'Moox\Core\Traits\Simple\SingleSimpleInListPage',
            'Moox\Core\Traits\Base\BaseInListPage',
        ];
        $this->traits['pages']['view'] = [
            'Moox\Core\Traits\Simple\SingleSimpleInViewPage',
            'Moox\Core\Traits\Base\BaseInViewPage',
        ];
        $this->traits['pages']['create'] = [
            'Moox\Core\Traits\Simple\SingleSimpleInCreatePage',
            'Moox\Core\Traits\Base\BaseInCreatePage',
        ];
        $this->traits['pages']['edit'] = [
            'Moox\Core\Traits\Simple\SingleSimpleInEditPage',
            'Moox\Core\Traits\Base\BaseInEditPage',
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
