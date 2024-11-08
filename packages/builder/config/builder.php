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

    /*
    |--------------------------------------------------------------------------
    | Resources
    |--------------------------------------------------------------------------
    |
    | The following configuration is done per Filament resource.
    |
    */

    'resources' => [
        'item' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//builder::translations.item',
            'plural' => 'trans//builder::translations.items',

            /*
            |--------------------------------------------------------------------------
            | Tabs
            |--------------------------------------------------------------------------
            |
            | Define the tabs for the Builder table. They are optional, but
            | pretty awesome to filter the table by certain values.
            | You may simply do a 'tabs' => [], to disable them.
            |
            */

            'tabs' => [
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
            ],

            /*
            |--------------------------------------------------------------------------
            | Taxonomies
            |--------------------------------------------------------------------------
            |
            | This array contains the taxonomies that should be shown.
            | This is work in progress and not yet fully documented.
            |
            */

            'taxonomies' => [
                'categories' => [
                    'label' => 'Categories',
                    'model' => \Moox\Category\Models\Category::class,
                    'table' => 'categorizables',
                    'relationship' => 'categorizable',
                    'foreignKey' => 'categorizable_id',
                    'relatedKey' => 'category_id',
                    'createForm' => \Moox\Category\Forms\TaxonomyCreateForm::class,
                    'hierarchical' => true,
                ],
                'tags' => [
                    'label' => 'Tags',
                    'model' => \Moox\Tag\Models\Tag::class,
                    'table' => 'taggables',
                    'relationship' => 'taggable',
                    'foreignKey' => 'taggable_id',
                    'relatedKey' => 'tag_id',
                    'createForm' => \Moox\Tag\Forms\TaxonomyCreateForm::class,
                ],
            ],
        ],
        'full-item' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//builder::translations.full-item',
            'plural' => 'trans//builder::translations.full-items',

            /*
            |--------------------------------------------------------------------------
            | Tabs
            |--------------------------------------------------------------------------
            |
            | Define the tabs for the Builder table. They are optional, but
            | pretty awesome to filter the table by certain values.
            | You may simply do a 'tabs' => [], to disable them.
            |
            */

            'tabs' => [
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
            ],

            /*
            |--------------------------------------------------------------------------
            | Taxonomies
            |--------------------------------------------------------------------------
            |
            | This array contains the taxonomies that should be shown.
            | This is work in progress and not yet fully documented.
            |
            */

            'taxonomies' => [
                'categories' => [
                    'label' => 'Categories',
                    'model' => \Moox\Category\Models\Category::class,
                    'table' => 'categorizables',
                    'relationship' => 'categorizable',
                    'foreignKey' => 'categorizable_id',
                    'relatedKey' => 'category_id',
                    'createForm' => \Moox\Category\Forms\TaxonomyCreateForm::class,
                    'hierarchical' => true,
                ],
                'tags' => [
                    'label' => 'Tags',
                    'model' => \Moox\Tag\Models\Tag::class,
                    'table' => 'taggables',
                    'relationship' => 'taggable',
                    'foreignKey' => 'taggable_id',
                    'relatedKey' => 'tag_id',
                    'createForm' => \Moox\Tag\Forms\TaxonomyCreateForm::class,
                ],
            ],
        ],
        'simple-item' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//builder::translations.simple-item',
            'plural' => 'trans//builder::translations.simple-items',

            /*
            |--------------------------------------------------------------------------
            | Tabs
            |--------------------------------------------------------------------------
            |
            | Define the tabs for the Builder table. They are optional, but
            | pretty awesome to filter the table by certain values.
            | You may simply do a 'tabs' => [], to disable them.
            |
            */

            'tabs' => [
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
            ],

            /*
            |--------------------------------------------------------------------------
            | Taxonomies
            |--------------------------------------------------------------------------
            |
            | This array contains the taxonomies that should be shown.
            | This is work in progress and not yet fully documented.
            |
            */

            'taxonomies' => [
                'categories' => [
                    'label' => 'Categories',
                    'model' => \Moox\Category\Models\Category::class,
                    'table' => 'categorizables',
                    'relationship' => 'categorizable',
                    'foreignKey' => 'categorizable_id',
                    'relatedKey' => 'category_id',
                    'createForm' => \Moox\Category\Forms\TaxonomyCreateForm::class,
                    'hierarchical' => true,
                ],
                'tags' => [
                    'label' => 'Tags',
                    'model' => \Moox\Tag\Models\Tag::class,
                    'table' => 'taggables',
                    'relationship' => 'taggable',
                    'foreignKey' => 'taggable_id',
                    'relatedKey' => 'tag_id',
                    'createForm' => \Moox\Tag\Forms\TaxonomyCreateForm::class,
                ],
            ],
        ],
        'simple-taxonomy' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//builder::translations.simple-taxonomy',
            'plural' => 'trans//builder::translations.simple-taxonomies',

            /*
            |--------------------------------------------------------------------------
            | Tabs
            |--------------------------------------------------------------------------
            |
            | Define the tabs for the Builder table. They are optional, but
            | pretty awesome to filter the table by certain values.
            | You may simply do a 'tabs' => [], to disable them.
            |
            */

            'tabs' => [
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
            ],

            /*
            |--------------------------------------------------------------------------
            | Taxonomies
            |--------------------------------------------------------------------------
            |
            | This array contains the taxonomies that should be shown.
            | This is work in progress and not yet fully documented.
            |
            */

            'taxonomies' => [
                'categories' => [
                    'label' => 'Categories',
                    'model' => \Moox\Category\Models\Category::class,
                    'table' => 'categorizables',
                    'relationship' => 'categorizable',
                    'foreignKey' => 'categorizable_id',
                    'relatedKey' => 'category_id',
                    'createForm' => \Moox\Category\Forms\TaxonomyCreateForm::class,
                    'hierarchical' => true,
                ],
                'tags' => [
                    'label' => 'Tags',
                    'model' => \Moox\Tag\Models\Tag::class,
                    'table' => 'taggables',
                    'relationship' => 'taggable',
                    'foreignKey' => 'taggable_id',
                    'relatedKey' => 'tag_id',
                    'createForm' => \Moox\Tag\Forms\TaxonomyCreateForm::class,
                ],
            ],
        ],
        'nested-taxonomy' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//builder::translations.nested-taxonomy',
            'plural' => 'trans//builder::translations.nested-taxonomies',

            /*
            |--------------------------------------------------------------------------
            | Tabs
            |--------------------------------------------------------------------------
            |
            | Define the tabs for the Builder table. They are optional, but
            | pretty awesome to filter the table by certain values.
            | You may simply do a 'tabs' => [], to disable them.
            |
            */

            'tabs' => [
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
            ],

            /*
            |--------------------------------------------------------------------------
            | Taxonomies
            |--------------------------------------------------------------------------
            |
            | This array contains the taxonomies that should be shown.
            | This is work in progress and not yet fully documented.
            |
            */

            'taxonomies' => [
                'categories' => [
                    'label' => 'Categories',
                    'model' => \Moox\Category\Models\Category::class,
                    'table' => 'categorizables',
                    'relationship' => 'categorizable',
                    'foreignKey' => 'categorizable_id',
                    'relatedKey' => 'category_id',
                    'createForm' => \Moox\Category\Forms\TaxonomyCreateForm::class,
                    'hierarchical' => true,
                ],
                'tags' => [
                    'label' => 'Tags',
                    'model' => \Moox\Tag\Models\Tag::class,
                    'table' => 'taggables',
                    'relationship' => 'taggable',
                    'foreignKey' => 'taggable_id',
                    'relatedKey' => 'tag_id',
                    'createForm' => \Moox\Tag\Forms\TaxonomyCreateForm::class,
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation Group
    |--------------------------------------------------------------------------
    |
    | The translatable title of the navigation group in the
    | Filament Admin Panel. Instead of a translatable
    | string, you may also use a simple string.
    |
    */

    'navigation_group' => 'trans//builder::translations.builder',

    /*
    |--------------------------------------------------------------------------
    | Navigation Sort
    |--------------------------------------------------------------------------
    |
    | This value is the sort order of the navigation item in the
    | Filament Admin Panel. If you use a bunch of Moox
    | plugins, everything should be in order.
    |
    */

    'navigation_sort' => 9990,

    /*
    |--------------------------------------------------------------------------
    | Item Types
    |--------------------------------------------------------------------------
    |
    | This array contains the types of items entities. You can delete
    | the types you don't need and add new ones. If you don't need
    | types, you can empty this array like this: 'types' => [],
    |
    */

    'types' => [
        'post' => 'Post',
        'page' => 'Page',
    ],

    /*
    |--------------------------------------------------------------------------
    | Author Model
    |--------------------------------------------------------------------------
    |
    | This sets the user model that can be used as author. It should be an
    | authenticatable model and support the morph relationship.
    | It should have fields similar to Moox User or WpUser.
    |
    */

    'author_model' => \Moox\User\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Allow Slug Change - WIP
    |--------------------------------------------------------------------------
    |
    | // TODO: Work in progress.
    |
    */

    'allow_slug_change_after_saved' => env('ALLOW_SLUG_CHANGE_AFTER_SAVED', true),
    'allow_slug_change_after_publish' => env('ALLOW_SLUG_CHANGE_AFTER_PUBLISH', false),

    /*
    |--------------------------------------------------------------------------
    | Blocks
    |--------------------------------------------------------------------------
    |
    | Define the available blocks that can be used to build resources.
    | Mute existing blocks or add your own blocks as you like.
    |
    */

    'blocks' => [
        'author' => \Moox\Builder\Blocks\Author::class,
        'bool' => \Moox\Builder\Blocks\Bool::class,
        'builder' => \Moox\Builder\Blocks\Builder::class,
        'checkbox-list' => \Moox\Builder\Blocks\CheckboxList::class,
        'color-picker' => \Moox\Builder\Blocks\ColorPicker::class,
        'date' => \Moox\Builder\Blocks\Date::class,
        'date-time' => \Moox\Builder\Blocks\DateTime::class,
        'file-upload' => \Moox\Builder\Blocks\FileUpload::class,
        'hidden' => \Moox\Builder\Blocks\Hidden::class,
        'image' => \Moox\Builder\Blocks\Image::class,
        'key-value' => \Moox\Builder\Blocks\KeyValue::class,
        'markdown-editor' => \Moox\Builder\Blocks\MarkdownEditor::class,
        'multi-select' => \Moox\Builder\Blocks\MultiSelect::class,
        'number' => \Moox\Builder\Blocks\Number::class,
        'publish' => \Moox\Builder\Blocks\Publish::class,
        'radio' => \Moox\Builder\Blocks\Radio::class,
        'relationship' => \Moox\Builder\Blocks\Relationship::class,
        'repeater' => \Moox\Builder\Blocks\Repeater::class,
        'rich-editor' => \Moox\Builder\Blocks\RichEditor::class,
        'select' => \Moox\Builder\Blocks\Select::class,
        'soft-delete' => \Moox\Builder\Blocks\SoftDelete::class,
        'tags-input' => \Moox\Builder\Blocks\TagsInput::class,
        'text' => \Moox\Builder\Blocks\Text::class,
        'textarea' => \Moox\Builder\Blocks\TextArea::class,
        'title-with-slug' => \Moox\Builder\Blocks\TitleWithSlug::class,
        'toggle' => \Moox\Builder\Blocks\Toggle::class,
        'toggle-buttons' => \Moox\Builder\Blocks\ToggleButtons::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Build Contexts
    |--------------------------------------------------------------------------
    |
    | Define the available build contexts and their configurations.
    | Each context can have its own path and namespace settings,
    | template and generator, and also can do migrations.
    |
    */

    'contexts' => [
        'app' => [
            'base_path' => app_path(),
            'base_namespace' => 'App',
            'classes' => [
                'model' => [
                    'path' => '%BasePath%\Models',
                    'namespace' => '%BaseNamespace%\\Models',
                    'template' => __DIR__.'/../src/Templates/Entity/model.php.stub',
                    'generator' => \Moox\Builder\Generators\Entity\ModelGenerator::class,
                ],
                'resource' => [
                    'path' => '%BasePath%\Resources',
                    'namespace' => '%BaseNamespace%\\Resources',
                    'template' => __DIR__.'/../src/Templates/Entity/resource.php.stub',
                    'page_templates' => [
                        'List' => __DIR__.'/../src/Templates/Entity/pages/list.php.stub',
                        'Create' => __DIR__.'/../src/Templates/Entity/pages/create.php.stub',
                        'Edit' => __DIR__.'/../src/Templates/Entity/pages/edit.php.stub',
                        'View' => __DIR__.'/../src/Templates/Entity/pages/view.php.stub',
                    ],
                    'generator' => \Moox\Builder\Generators\Entity\ResourceGenerator::class,
                ],
                'migration' => [
                    'path' => 'database\migrations',
                    'template' => __DIR__.'/../src/Templates/Entity/migration.php.stub',
                    'generator' => \Moox\Builder\Generators\Entity\MigrationGenerator::class,
                ],
                'plugin' => [
                    'path' => '%BasePath%\Filament\Plugins',
                    'namespace' => '%BaseNamespace%\\Filament\\Plugins',
                    'template' => __DIR__.'/../src/Templates/Entity/plugin.php.stub',
                    'generator' => \Moox\Builder\Generators\Entity\PluginGenerator::class,
                ],
                'translation' => [
                    'path' => 'lang\entities',
                    'template' => __DIR__.'/../src/Templates/Entity/translation.php.stub',
                    'generator' => \Moox\Builder\Generators\Entity\TranslationGenerator::class,
                ],
                'config' => [
                    'path' => 'config\entities',
                    'template' => __DIR__.'/../src/Templates/Entity/config.php.stub',
                    'generator' => \Moox\Builder\Generators\Entity\ConfigGenerator::class,
                ],
            ],
        ],
        'package' => [
            'base_path' => '$PackagePath',
            'base_namespace' => '$PackageNamespace',
            'classes' => [
                'model' => [
                    'path' => '%BasePath%\src\Models',
                    'namespace' => '%BaseNamespace%\\Models',
                    'template' => __DIR__.'/../src/Templates/Entity/model.php.stub',
                    'generator' => \Moox\Builder\Generators\Entity\ModelGenerator::class,
                ],
                'resource' => [
                    'path' => '%BasePath%\src\Resources',
                    'namespace' => '%BaseNamespace%\\Resources',
                    'template' => __DIR__.'/../src/Templates/Entity/resource.php.stub',
                    'page_templates' => [
                        'List' => __DIR__.'/../src/Templates/Entity/pages/list.php.stub',
                        'Create' => __DIR__.'/../src/Templates/Entity/pages/create.php.stub',
                        'Edit' => __DIR__.'/../src/Templates/Entity/pages/edit.php.stub',
                        'View' => __DIR__.'/../src/Templates/Entity/pages/view.php.stub',
                    ],
                    'generator' => \Moox\Builder\Generators\Entity\ResourceGenerator::class,
                ],
                'migration_stub' => [
                    'path' => '%BasePath%\database\migrations',
                    'template' => __DIR__.'/../src/Templates/Entity/migration.php.stub',
                    'generator' => \Moox\Builder\Generators\Entity\MigrationGenerator::class,
                ],
                'plugin' => [
                    'path' => '%BasePath%\src',
                    'namespace' => '%BaseNamespace%',
                    'template' => __DIR__.'/../src/Templates/Entity/plugin.php.stub',
                    'generator' => \Moox\Builder\Generators\Entity\PluginGenerator::class,
                ],
                'translation' => [
                    'path' => '%BasePath%\resources\lang\entities',
                    'template' => __DIR__.'/../src/Templates/Entity/translation.php.stub',
                    'generator' => \Moox\Builder\Generators\Entity\TranslationGenerator::class,
                ],
                'config' => [
                    'path' => '%BasePath%\config\entities',
                    'template' => __DIR__.'/../src/Templates/Entity/config.php.stub',
                    'generator' => \Moox\Builder\Generators\Entity\ConfigGenerator::class,
                ],
            ],
        ],
        'preview' => [
            'base_path' => app_path('Builder'),
            'base_namespace' => 'App\\Builder',
            'classes' => [
                'model' => [
                    'path' => '%BasePath%\Models',
                    'namespace' => '%BaseNamespace%\\Models',
                    'template' => __DIR__.'/../src/Templates/Entity/model.php.stub',
                    'generator' => \Moox\Builder\Generators\Entity\ModelGenerator::class,
                ],
                'resource' => [
                    'path' => '%BasePath%\Resources',
                    'namespace' => '%BaseNamespace%\\Resources',
                    'template' => __DIR__.'/../src/Templates/Entity/resource.php.stub',
                    'page_templates' => [
                        'List' => __DIR__.'/../src/Templates/Entity/pages/list.php.stub',
                        'Create' => __DIR__.'/../src/Templates/Entity/pages/create.php.stub',
                        'Edit' => __DIR__.'/../src/Templates/Entity/pages/edit.php.stub',
                        'View' => __DIR__.'/../src/Templates/Entity/pages/view.php.stub',
                    ],
                    'generator' => \Moox\Builder\Generators\Entity\ResourceGenerator::class,
                ],
                'migration' => [
                    'path' => 'database\migrations',
                    'template' => __DIR__.'/../src/Templates/Entity/migration.php.stub',
                    'generator' => \Moox\Builder\Generators\Entity\MigrationGenerator::class,
                ],
                'translation' => [
                    'path' => 'lang\previews',
                    'template' => __DIR__.'/../src/Templates/Entity/translation.php.stub',
                    'generator' => \Moox\Builder\Generators\Entity\TranslationGenerator::class,
                ],
                'config' => [
                    'path' => 'config\previews',
                    'template' => __DIR__.'/../src/Templates/Entity/config.php.stub',
                    'generator' => \Moox\Builder\Generators\Entity\ConfigGenerator::class,
                ],
            ],
            'should_migrate' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Presets
    |--------------------------------------------------------------------------
    |
    | Register available presets that can be used to quickly scaffold resources.
    | Each preset key must match the class name in lowercase without 'Preset'.
    |
    */

    'presets' => [
        'simple-item' => [
            'class' => \Moox\Builder\Presets\SimpleItemPreset::class,
            'generators' => ['model', 'migration', 'resource'],
        ],
        'publishable-item' => [
            'class' => \Moox\Builder\Presets\PublishableItemPreset::class,
            'generators' => ['model', 'migration', 'resource'],
        ],
        'full-item' => [
            'class' => \Moox\Builder\Presets\FullItemPreset::class,
            'generators' => ['model', 'migration', 'resource', 'factory'],
        ],
        'simple-taxonomy' => [
            'class' => \Moox\Builder\Presets\SimpleTaxonomyPreset::class,
            'generators' => ['model', 'migration', 'resource'],
        ],
        'nested-taxonomy' => [
            'class' => \Moox\Builder\Presets\NestedTaxonomyPreset::class,
            'generators' => ['model', 'migration', 'resource'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Package Generator
    |--------------------------------------------------------------------------
    |
    | Define the available generators for the package builder and their
    | templates. You can also add your own generators and templates.
    |
    */

    'package_generator' => [
        'archtest' => [
            'template' => __DIR__.'/../src/Templates/package/archtest.php.stub',
            'generator' => \Moox\Builder\Generators\Package\ArchTestGenerator::class,
        ],
        'changelog' => [
            'template' => __DIR__.'/../src/Templates/package/changelog.md.stub',
            'generator' => \Moox\Builder\Generators\Package\ChangelogGenerator::class,
        ],
        'composer' => [
            'template' => __DIR__.'/../src/Templates/package/composer.json.stub',
            'generator' => \Moox\Builder\Generators\Package\ComposerJsonGenerator::class,
        ],
        'config' => [
            'template' => __DIR__.'/../src/Templates/package/config.php.stub',
            'generator' => \Moox\Builder\Generators\Package\ConfigFileGenerator::class,
        ],
        'funding' => [
            'template' => __DIR__.'/../src/Templates/package/funding.yml.stub',
            'generator' => \Moox\Builder\Generators\Package\FundingGenerator::class,
        ],
        'gitignore' => [
            'template' => __DIR__.'/../src/Templates/package/gitignore.stub',
            'generator' => \Moox\Builder\Generators\Package\GitignoreGenerator::class,
        ],
        'install' => [
            'template' => __DIR__.'/../src/Templates/package/install.php.stub',
            'generator' => \Moox\Builder\Generators\Package\InstallGenerator::class,
        ],
        'license' => [
            'template' => __DIR__.'/../src/Templates/package/license.md.stub',
            'generator' => \Moox\Builder\Generators\Package\LicenceGenerator::class,
        ],
        'panelprovider' => [
            'template' => __DIR__.'/../src/Templates/package/panelprovider.php.stub',
            'generator' => \Moox\Builder\Generators\Package\PanelProviderGenerator::class,
        ],
        'pest' => [
            'template' => __DIR__.'/../src/Templates/package/pest.php.stub',
            'generator' => \Moox\Builder\Generators\Package\PestGenerator::class,
        ],
        'readme' => [
            'template' => __DIR__.'/../src/Templates/package/readme.md.stub',
            'generator' => \Moox\Builder\Generators\Package\ReadmeGenerator::class,
        ],
        'security' => [
            'template' => __DIR__.'/../src/Templates/package/security.md.stub',
            'generator' => \Moox\Builder\Generators\Package\SecurityGenerator::class,
        ],
        'serviceprovider' => [
            'template' => __DIR__.'/../src/Templates/package/serviceprovider.php.stub',
            'generator' => \Moox\Builder\Generators\Package\ServiceProviderGenerator::class,
        ],
        'testcase' => [
            'template' => __DIR__.'/../src/Templates/package/testcase.php.stub',
            'generator' => \Moox\Builder\Generators\Package\TestCaseGenerator::class,
        ],
        'translation' => [
            'template' => __DIR__.'/../src/Templates/package/translation.php.stub',
            'generator' => \Moox\Builder\Generators\Package\TranslationGenerator::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Package Activator
    |--------------------------------------------------------------------------
    |
    | To activate a package, we need to require it and run the install
    | command. You can define your own activator if you like.
    |
    */

    'package_activator' => \Moox\Builder\Services\PackageActivator::class,

    /*
    |--------------------------------------------------------------------------
    | Package Publisher
    |--------------------------------------------------------------------------
    |
    | Publishing a package is a multi step process. You can define your own.
    |
    */

    'package_publisher' => [
        'git' => \Moox\Builder\Services\PackageGitPublisher::class,
        'github' => \Moox\Builder\Services\PackageGitHubPublisher::class,
        'packagist' => \Moox\Builder\Services\PackagePackagistPublisher::class,
    ],

    // GitHub API Token
    'github_api_token' => env('BUILDER_GITHUB_API_TOKEN'),

    // Packagist API Token
    'packagist_username' => env('BUILDER_PACKAGIST_USERNAME'),
    'packagist_api_token' => env('BUILDER_PACKAGIST_API_TOKEN'),
];
