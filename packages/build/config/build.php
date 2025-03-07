<?php

/*
|--------------------------------------------------------------------------
| Moox Configuration
|--------------------------------------------------------------------------
|
| This configuration file uses translatable strings. If you want to
| translate the strings, you can do so in the language files
| published from moox_core. Example:
|
| 'trans//core::core.all',
| loads from common.php
| outputs 'All'
|
*/

return [

    'package_templates' => [
        'skeleton' => [
            'name' => 'Empty Package',
            'select' => 'No Entities, just an empty package',
            'motivation' => 'Easy!',
            'emoji' => 'emojiSmile',
            'subject' => 'Package',
            'sentence' => 'using Moox Skeleton.',
            'path' => 'packages/skeleton',
            'website' => 'https://moox.org/docs/skeleton',
            'entity_files' => [
                // Skeleton has no entity files
            ],
            'replace_strings' => [
                'skeleton.jpg' => 'made-with-moox.jpg',
                'Moox Developer' => '%%authorName%%',
                'dev@moox.org' => '%%authorEmail%%',
                'Skeleton' => '%%packageName%%',
                'skeleton' => '%%packageSlug%%',
                'This template is used for generating Laravel packages, all Moox packages are built with this template.' => '%%description%%',
                'not used as installed package, only used as template for new Moox packages' => 'we do not know yet',
                'creating simple Laravel packages' => 'we do not know yet',
            ],
            'rename_files' => [
                'config/skeleton.php' => 'config/%%packageSlug%%.php',
                'src/SkeletonServiceProvider.php' => 'src/%%packageName%%ServiceProvider.php',
            ],
            'replace_sections' => [
                'README.md' => [
                    '/<!--shortdesc-->.*<!--\/shortdesc-->/s' => '%%description%%',
                ]],
        ],
        'item' => [
            'name' => 'Simple Item',
            'select' => 'Moox Simple Item - entity with simple fields',
            'motivation' => 'Cool!',
            'emoji' => 'emojiCool',
            'subject' => 'Item',
            'sentence' => 'with simple fields.',
            'path' => 'packages/item',
            'website' => 'https://moox.org/docs/simple-item',
            'entity_files' => [
                'config/item.php',
                'database/factories/ItemFactory.php',
                'database/migrations/create_items_table.php.stub',
                'resources/lang/*/translations.php',
                'src/ItemPlugin.php',
                'src/Models/Item.php',
                'src/Resources/ItemResource.php',
                'src/Resources/ItemResource\Pages\CreateItem.php',
                'src/Resources/ItemResource\Pages\EditItem.php',
                'src/Resources/ItemResource\Pages\ListItems.php',
                'src/Resources/ItemResource\Pages\ViewItem.php',
                // TODO: Add RelationManager, if exists
            ],
        ],
        'item_archive' => [
            'name' => 'Archive Item',
            'select' => 'Moox Archive Item - entity with soft delete',
            'motivation' => 'Great!',
            'emoji' => 'emojiParty',
            'subject' => 'Item',
            'sentence' => 'with Soft Delete.',
            'path' => 'packages/item-archive',
            'website' => 'https://moox.org/docs/archive-item',
            'entity_files' => [
                'config/archive-item.php',
                'database/factories/ArchiveItemFactory.php',
                'database/migrations/create_archive_items_table.php.stub',
                'resources/lang/*/translations.php',
                'src/ArchiveItemPlugin.php',
                'src/Models/ArchiveItem.php',
                'src/Resources/ArchiveItemResource.php',
                'src/Resources/ArchiveItemResource\Pages\CreateArchiveItem.php',
                'src/Resources/ArchiveItemResource\Pages\EditArchiveItem.php',
                'src/Resources/ArchiveItemResource\Pages\ListArchiveItems.php',
                'src/Resources/ArchiveItemResource\Pages\ViewArchiveItem.php',
                // TODO: Add RelationManager, if exists
                'rename_files' => [
                    'src/Models/Item.php' => 'src/Models/ArchiveItem.php',
                ],
                'replace_strings' => [
                    // TODO:    '' => '',
                ],
                'replace_sections' => [
                    // TODO:    '' => '',
                ],
            ],
        ],
        'item_publish' => [
            'name' => 'Publish Item',
            'select' => 'Moox Publish Item - entity with publish feature',
            'motivation' => 'Wheew!',
            'emoji' => 'emojiRocket',
            'subject' => 'Item',
            'sentence' => 'with Publish feature.',
            'path' => 'packages/item-publish',
            'website' => 'https://moox.org/docs/publish-item',
            'entity_files' => [
                'config/publish-item.php',
                'database/factories/PublishItemFactory.php',
                'database/migrations/create_publish_items_table.php.stub',
                'resources/lang/*/translations.php',
                'src/PublishItemPlugin.php',
                'src/Models/PublishItem.php',
                'src/Resources/PublishItemResource.php',
                'src/Resources/PublishItemResource\Pages\CreatePublishItem.php',
                'src/Resources/PublishItemResource\Pages\EditPublishItem.php',
                'src/Resources/PublishItemResource\Pages\ListPublishItems.php',
                'src/Resources/PublishItemResource\Pages\ViewPublishItem.php',
                // TODO: Add RelationManager, if exists
            ],
            'replace_strings' => [
                // TODO:    '' => '',
            ],
            'replace_sections' => [
                // TODO:    '' => '',
            ],
        ],
        'taxonomy' => [
            'name' => 'Simple Taxonomy',
            'select' => 'Moox Simple Taxonomy - a flat taxonomy',
            'motivation' => 'Cool!',
            'emoji' => 'emojiCool',
            'subject' => 'Taxonomy',
            'sentence' => 'for tagging.',
            'path' => 'packages/tag',
            'website' => 'https://moox.org/docs/simple-taxonomy',
            'entity_files' => [
                'config/tag.php',
                'database/factories/TagFactory.php',
                'database/migrations/create_tags_table.php.stub',
                'resources/lang/*/translations.php',
                'src/TagPlugin.php',
                'src/Forms/TaxonomyCreateForm.php',
                'src/Models/Tag.php',
                'src/Resources/TagResource.php',
                'src/Resources/TagResource\Pages\CreateTag.php',
                'src/Resources/TagResource\Pages\EditTag.php',
                'src/Resources/TagResource\Pages\ListTags.php',
                'src/Resources/TagResource\Pages\ViewTag.php',
            ],
            'replace_strings' => [
                // TODO:    '' => '',
            ],
            'replace_sections' => [
                // TODO:    '' => '',
            ],
        ],
        'nested_taxonomy' => [
            'name' => 'Nested Taxonomy',
            'select' => 'Moox Nested Taxonomy - a hierarchical taxonomy',
            'motivation' => 'Great!',
            'emoji' => 'emojiParty',
            'subject' => 'Taxonomy',
            'sentence' => 'with Nested Set.',
            'path' => 'packages/category',
            'website' => 'https://moox.org/docs/nested-taxonomy',
            'entity_files' => [
                'config/category.php',
                'database/factories/CategoryFactory.php',
                'database/migrations/create_categories_table.php.stub',
                'database/migrations/create_categorizables_table.php.stub',
                'resources/lang/*/translations.php',
                'src/CategoryPlugin.php',
                'src/Forms/TaxonomyCreateForm.php',
                'src/Models/Category.php',
                'src/Resources/CategoryResource.php',
                'src/Resources/CategoryResource\Pages\CreateCategory.php',
                'src/Resources/CategoryResource\Pages\EditCategory.php',
                'src/Resources/CategoryResource\Pages\ListCategories.php',
                'src/Resources/CategoryResource\Pages\ViewCategory.php',
            ],
            'replace_strings' => [
                // TODO:    '' => '',
            ],
            'replace_sections' => [
                // TODO:    '' => '',
            ],
        ],
        'module' => [
            'name' => 'Module',
            'select' => 'Moox Module - to extend an existing entity',
            'motivation' => 'Wheew!',
            'emoji' => 'emojiRocket',
            'subject' => 'Module',
            'sentence' => 'that provides additional fields.',
            'path' => 'packages/module',
            'website' => 'https://moox.org/docs/module',
            'entity_files' => [
                'config/module.php',
                'database/factories/ModuleFactory.php',
                'database/migrations/create_modules_table.php.stub',
                'resources/lang/*/translations.php',
                'src/ModulePlugin.php',
                // TODO: Add entity files
            ],
            'replace_strings' => [
                // TODO:    '' => '',
            ],
            'replace_sections' => [
                // TODO:    '' => '',
            ],
        ],
        'theme' => [
            'name' => 'Theme',
            'select' => 'Moox Theme - to style the Frontend',
            'motivation' => 'Stylish!',
            'emoji' => 'emojiRainbow',
            'subject' => 'Theme',
            'sentence' => 'and create an awesome Website.',
            'path' => 'packages/theme-base',
            'website' => 'https://moox.org/docs/themes',
            'entity_files' => [
                // TODO: Theme has no entity files
            ],
            'replace_strings' => [
                // TODO:    '' => '',
            ],
            'replace_sections' => [
                // TODO:    '' => '',
            ],
        ],
    ],

    'default_author' => [
        'name' => 'Moox',
        'email' => 'dev@moox.org',
    ],

    'default_namespace' => 'Moox',

    'default_packagist' => 'moox',

    'package_path' => 'packages',
];
