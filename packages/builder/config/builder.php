<?php

use Moox\Builder\Blocks\Features\CustomDemo;
use Moox\Builder\Blocks\Features\SimpleStatus;
use Moox\Builder\Blocks\Features\SimpleType;
use Moox\Builder\Blocks\Features\Tabs;
use Moox\Builder\Blocks\Fields\Boolean;
use Moox\Builder\Blocks\Fields\Builder;
use Moox\Builder\Blocks\Fields\CheckboxList;
use Moox\Builder\Blocks\Fields\ColorPicker;
use Moox\Builder\Blocks\Fields\Date;
use Moox\Builder\Blocks\Fields\DateTime;
use Moox\Builder\Blocks\Fields\FileUpload;
use Moox\Builder\Blocks\Fields\Hidden;
use Moox\Builder\Blocks\Fields\Image;
use Moox\Builder\Blocks\Fields\KeyValue;
use Moox\Builder\Blocks\Fields\MarkdownEditor;
use Moox\Builder\Blocks\Fields\MultiSelect;
use Moox\Builder\Blocks\Fields\Number;
use Moox\Builder\Blocks\Fields\Radio;
use Moox\Builder\Blocks\Fields\Relationship;
use Moox\Builder\Blocks\Fields\Repeater;
use Moox\Builder\Blocks\Fields\RichEditor;
use Moox\Builder\Blocks\Fields\Select;
use Moox\Builder\Blocks\Fields\TagsInput;
use Moox\Builder\Blocks\Fields\Text;
use Moox\Builder\Blocks\Fields\TextArea;
use Moox\Builder\Blocks\Fields\Toggle;
use Moox\Builder\Blocks\Fields\ToggleButtons;
use Moox\Builder\Blocks\Sections\SimpleAddressSection;
use Moox\Builder\Blocks\Singles\Light;
use Moox\Builder\Blocks\Singles\Simple;
use Moox\Builder\Blocks\Singles\SoftDelete;
use Moox\Builder\Generators\Entity\ConfigGenerator;
use Moox\Builder\Generators\Entity\MigrationGenerator;
use Moox\Builder\Generators\Entity\ModelGenerator;
use Moox\Builder\Generators\Entity\PluginGenerator;
use Moox\Builder\Generators\Entity\ResourceGenerator;
use Moox\Builder\Generators\Entity\TranslationGenerator;
use Moox\Builder\Presets\FullItemPreset;
use Moox\Builder\Presets\LightItemPreset;
use Moox\Builder\Presets\SimpleItemPreset;
use Moox\Builder\Presets\SoftDeleteItemPreset;

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
            'boolean' => Boolean::class,
            'builder' => Builder::class,
            'checkbox-list' => CheckboxList::class,
            'color-picker' => ColorPicker::class,
            'date' => Date::class,
            'date-time' => DateTime::class,
            'file-upload' => FileUpload::class,
            'hidden' => Hidden::class,
            'image' => Image::class,
            'key-value' => KeyValue::class,
            'markdown-editor' => MarkdownEditor::class,
            'multi-select' => MultiSelect::class,
            'number' => Number::class,
            'radio' => Radio::class,
            'relationship' => Relationship::class,
            'repeater' => Repeater::class,
            'rich-editor' => RichEditor::class,
            'select' => Select::class,
            'tags-input' => TagsInput::class,
            'text' => Text::class,
            'textarea' => TextArea::class,
            'toggle' => Toggle::class,
            'toggle-buttons' => ToggleButtons::class,
        ],
        'features' => [
            'custom-demo' => CustomDemo::class,
            'simple-status' => SimpleStatus::class,
            'simple-type' => SimpleType::class,
            'tabs' => Tabs::class,
        ],
        'sections' => [
            'simple-address' => SimpleAddressSection::class,
        ],
        'singles' => [
            'light' => Light::class,
            'simple' => Simple::class,
            'soft-delete' => SoftDelete::class,
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
        'custom' => [
            'base_path' => app_path('Custom'),
            'base_namespace' => 'App',
            'generators' => [
                'model' => [
                    'path' => '%BasePath%\Models',
                    'namespace' => '%BaseNamespace%\\Models',
                    'template' => __DIR__.'/../src/Templates/Entity/model.php.stub',
                    'generator' => ModelGenerator::class,
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
                    'generator' => ResourceGenerator::class,
                ],
                'migration' => [
                    'path' => 'database\migrations',
                    'template' => __DIR__.'/../src/Templates/Entity/migration.php.stub',
                    'generator' => MigrationGenerator::class,
                ],
                'plugin' => [
                    'path' => '%BasePath%\Filament\Plugins',
                    'namespace' => '%BaseNamespace%\\Filament\\Plugins',
                    'template' => __DIR__.'/../src/Templates/Entity/plugin.php.stub',
                    'generator' => PluginGenerator::class,
                ],
                'translation' => [
                    'path' => 'lang\%locale%\entities',
                    'template' => __DIR__.'/../src/Templates/Entity/translation.php.stub',
                    'generator' => TranslationGenerator::class,
                ],
                'config' => [
                    'path' => 'config\entities',
                    'template' => __DIR__.'/../src/Templates/Entity/config.php.stub',
                    'generator' => ConfigGenerator::class,
                ],
            ],
        ],
        'app' => [
            'base_path' => app_path(),
            'base_namespace' => 'App',
            'generators' => [
                'model' => [
                    'path' => '%BasePath%\Models',
                    'namespace' => '%BaseNamespace%\\Models',
                    'template' => __DIR__.'/../src/Templates/Entity/model.php.stub',
                    'generator' => ModelGenerator::class,
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
                    'generator' => ResourceGenerator::class,
                ],
                'plugin' => [
                    'path' => '%BasePath%\Filament\Plugins',
                    'namespace' => '%BaseNamespace%\\Filament\\Plugins',
                    'template' => __DIR__.'/../src/Templates/Entity/plugin.php.stub',
                    'generator' => PluginGenerator::class,
                ],
                'migration' => [
                    'path' => 'database\migrations',
                    'template' => __DIR__.'/../src/Templates/Entity/migration.php.stub',
                    'generator' => MigrationGenerator::class,
                ],
                'translation' => [
                    'path' => 'lang\%locale%\entities',
                    'template' => __DIR__.'/../src/Templates/Entity/translation.php.stub',
                    'generator' => TranslationGenerator::class,
                ],
                'config' => [
                    'path' => 'config\entities',
                    'template' => __DIR__.'/../src/Templates/Entity/config.php.stub',
                    'generator' => ConfigGenerator::class,
                ],
            ],
        ],
        /*
        'package' => [
            'base_path' => '$PackagePath',
            'base_namespace' => '$PackageNamespace',
            'generators' => [
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
        */
        'preview' => [
            'base_path' => app_path('Builder'),
            'base_namespace' => 'App\\Builder',
            'generators' => [
                'model' => [
                    'path' => '%BasePath%\Models',
                    'namespace' => '%BaseNamespace%\\Models',
                    'template' => __DIR__.'/../src/Templates/Entity/model.php.stub',
                    'generator' => ModelGenerator::class,
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
                    'generator' => ResourceGenerator::class,
                ],
                'translation' => [
                    'path' => 'lang\%locale%\previews',
                    'template' => __DIR__.'/../src/Templates/Entity/translation.php.stub',
                    'generator' => TranslationGenerator::class,
                ],
                'config' => [
                    'path' => 'config\previews',
                    'template' => __DIR__.'/../src/Templates/Entity/config.php.stub',
                    'generator' => ConfigGenerator::class,
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
            'class' => LightItemPreset::class,
            'generators' => ['model', 'migration', 'resource'],
        ],
        'simple-item' => [
            'class' => SimpleItemPreset::class,
            'generators' => ['model', 'migration', 'resource'],
        ],
        'soft-delete-item' => [
            'class' => SoftDeleteItemPreset::class,
            'generators' => ['model', 'migration', 'resource'],
        ],
        'full-item' => [
            'class' => FullItemPreset::class,
            'generators' => ['model', 'migration', 'resource'],
        ],
    ],
];
