<?php

return [

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
        'fields' => [
            'boolean' => \Moox\Builder\Blocks\Filament\Boolean::class,
            'builder' => \Moox\Builder\Blocks\Filament\Builder::class,
            'checkbox-list' => \Moox\Builder\Blocks\Filament\CheckboxList::class,
            'color-picker' => \Moox\Builder\Blocks\Filament\ColorPicker::class,
            'date' => \Moox\Builder\Blocks\Filament\Date::class,
            'date-time' => \Moox\Builder\Blocks\Filament\DateTime::class,
            'file-upload' => \Moox\Builder\Blocks\Filament\FileUpload::class,
            'hidden' => \Moox\Builder\Blocks\Filament\Hidden::class,
            'image' => \Moox\Builder\Blocks\Filament\Image::class,
            'key-value' => \Moox\Builder\Blocks\Filament\KeyValue::class,
            'markdown-editor' => \Moox\Builder\Blocks\Filament\MarkdownEditor::class,
            'multi-select' => \Moox\Builder\Blocks\Filament\MultiSelect::class,
            'number' => \Moox\Builder\Blocks\Filament\Number::class,
            'radio' => \Moox\Builder\Blocks\Filament\Radio::class,
            'relationship' => \Moox\Builder\Blocks\Filament\Relationship::class,
            'repeater' => \Moox\Builder\Blocks\Filament\Repeater::class,
            'rich-editor' => \Moox\Builder\Blocks\Filament\RichEditor::class,
            'select' => \Moox\Builder\Blocks\Filament\Select::class,
            'tags-input' => \Moox\Builder\Blocks\Filament\TagsInput::class,
            'text' => \Moox\Builder\Blocks\Filament\Text::class,
            'textarea' => \Moox\Builder\Blocks\Filament\TextArea::class,
            'toggle' => \Moox\Builder\Blocks\Filament\Toggle::class,
            'toggle-buttons' => \Moox\Builder\Blocks\Filament\ToggleButtons::class,
        ],
        'features' => [
            'simple' => \Moox\Builder\Blocks\Singles\Simple::class,
            'soft-delete' => \Moox\Builder\Blocks\Singles\SoftDelete::class,
            'title-with-slug' => \Moox\Builder\Blocks\Moox\TitleWithSlug::class,
            'simple-status' => \Moox\Builder\Blocks\Features\SimpleStatus::class,
            'simple-type' => \Moox\Builder\Blocks\Features\SimpleType::class,
        ],
        'sections' => [
            'address' => \Moox\Builder\Blocks\Sections\AddressSection::class,
        ],
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
        'moox' => [
            'base_path' => app_path('Moox'),
            'base_namespace' => 'App\\Moox',
        ],
        'app' => [
            'base_path' => app_path(),
            'base_namespace' => 'App',
            'generators' => [
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
                    'path' => 'lang\%locale%\entities',
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
        'preview' => [
            'base_path' => app_path('Builder'),
            'base_namespace' => 'App\\Builder',
            'generators' => [
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
                'translation' => [
                    'path' => 'lang\%locale%\previews',
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
        'light-item' => [
            'class' => \Moox\Builder\Presets\LightItemPreset::class,
            'generators' => ['model', 'migration', 'resource'],
        ],
        'simple-item' => [
            'class' => \Moox\Builder\Presets\SimpleItemPreset::class,
            'generators' => ['model', 'migration', 'resource'],
        ],
        'soft-delete-item' => [
            'class' => \Moox\Builder\Presets\SoftDeleteItemPreset::class,
            'generators' => ['model', 'migration', 'resource'],
        ],
        'full-item' => [
            'class' => \Moox\Builder\Presets\FullItemPreset::class,
            'generators' => ['model', 'migration', 'resource'],
        ],
        'related-item' => [
            'class' => \Moox\Builder\Presets\RelatedItemPreset::class,
            'generators' => ['model', 'migration', 'resource'],
        ],
        'simple-taxonomy' => [
            'class' => \Moox\Builder\Presets\SimpleTaxonomyPreset::class,
            'generators' => ['model', 'migration', 'resource'],
        ],
    ],
];
