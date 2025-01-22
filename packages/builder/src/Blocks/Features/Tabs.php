<?php

namespace Moox\Builder\Blocks\Features;

use Moox\Core\Traits\Tabs\TabsInResource;
use Moox\Core\Traits\Tabs\TabsInListPage;
use Moox\Builder\Blocks\AbstractBlock;

class Tabs extends AbstractBlock
{
    public function __construct(
        string $name = 'tabs',
        string $label = 'Tabs',
        string $description = 'Generates dynamic tabs for the resource',
    ) {
        parent::__construct($name, $label, $description);

        $this->traits['resource'] = [TabsInResource::class];
        $this->traits['pages']['list'] = [TabsInListPage::class];

        $this->methods['pages']['list']['mount'] = '$this->mountTabsInListPage();';

        $this->methods['pages']['list']['getTabs'] = '
            public function getTabs(): array {
                return $this->getDynamicTabs(\'{{ entityKey }}.tabs\', \{{ namespace }}\{{ entity }}::class);
            }';

        $this->config['tabs'] = [
            'all' => [
                'label' => 'trans//core::core.all',
                'icon' => 'gmdi-filter-list',
                'query' => [],
            ],
        ];
    }

    public function getTabs(): array
    {
        return $this->config['tabs'];
    }
}
