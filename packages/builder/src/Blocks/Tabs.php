<?php

namespace Moox\Builder\Blocks;

class Tabs extends AbstractBlock
{
    public function __construct(
        string $name = 'tabs',
        string $label = 'Tabs',
        string $description = 'Generates dynamic tabs for the resource',
    ) {
        parent::__construct($name, $label, $description);

        $this->traits['resource'] = ['Moox\Core\Traits\TabsInResource'];
        $this->traits['listPage'] = ['Moox\Core\Traits\TabsInListPage'];

        $this->methods['listPage'] = [
            'public function mount(): void {
                parent::mount();
                $this->mountTabsInListPage();
            }',
            'public function getTabs(): array {
                return $this->getDynamicTabs(\'{{ entity }}.tabs\', {{ entity }}::class);
            }',
        ];

        // better would be, what should do the above

        $this->methods['listPage']['mount'] = '$this->mountTabsInListPage();';

        // and then additionally we need just the method name

        $this->methods['listPage'] = [
            'public function getTabs(): array {
                return $this->getDynamicTabs(\'builder.resources.item.tabs\', Item::class);
            }',
        ];

        // besides that, I am not sure about the path to the tabs here, I think it depends on context

        // config is not implemented yet, but this would be an example

        // Wrap into
        // 'tabs' => [
        //     ...
        // ],

        $this->config['tabs'] = [
            'all' => [
                'label' => 'trans//core::core.all',
                    'icon' => 'gmdi-filter-list',
                    'query' => [
                        [
                            'field' => 'deleted_at',
                            'operator' => '=',
                            'value' => null,
                        ],
                    ],
                ],
            ",

            // finally, as this is the blocks config for a publish item,
            // I would add a basic block config here in tabs.php and
            // allow to overwrite it from all other blocks
            // if multiple blocks are used, I would expect the tabs to be merged

            /*

                            'published' => [
                    'label' => 'trans//core::core.published',
                    'icon' => 'gmdi-check-circle',
                    'query' => [
                        [
                            'field' => 'publish_at',
                            'operator' => '<=',
                            'value' => function () {
                                return now();
                            },
                        ],
                        [
                            'field' => 'deleted_at',
                            'operator' => '=',
                            'value' => null,
                        ],
                    ],
                ],
                'scheduled' => [
                    'label' => 'trans//core::core.scheduled',
                    'icon' => 'gmdi-schedule',
                    'query' => [
                        [
                            'field' => 'publish_at',
                            'operator' => '>',
                            'value' => function () {
                                return now();
                            },
                        ],
                        [
                            'field' => 'deleted_at',
                            'operator' => '=',
                            'value' => null,
                        ],
                    ],
                ],
                'draft' => [
                    'label' => 'trans//core::core.draft',
                    'icon' => 'gmdi-text-snippet',
                    'query' => [
                        [
                            'field' => 'publish_at',
                            'operator' => '=',
                            'value' => null,
                        ],
                        [
                            'field' => 'deleted_at',
                            'operator' => '=',
                            'value' => null,
                        ],
                    ],
                ],
                'deleted' => [
                    'label' => 'trans//core::core.deleted',
                    'icon' => 'gmdi-delete',
                    'query' => [
                        [
                            'field' => 'deleted_at',
                            'operator' => '!=',
                            'value' => null,
                        ],
                    ],
                ],

            */
        ];
    }
}
