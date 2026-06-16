<?php

declare(strict_types=1);

namespace Moox\Tree\Tests\Support;

use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Moox\Tree\Config\TreeIndexConfiguration;
use Moox\Tree\Contracts\ConfiguresTreeIndex;
use Moox\Tree\Tests\Models\TreeNode;

class TestForwardTreeResource extends Resource implements ConfiguresTreeIndex
{
    protected static ?string $model = TreeNode::class;

    public static function getTitleColumn(): TextColumn
    {
        return TextColumn::make('label')->searchable();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                self::getTitleColumn(),
            ])
            ->filters([
                TernaryFilter::make('is_visible'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'create' => TestCreateTreeNodePage::route('/create'),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('label')->required(),
        ]);
    }

    public static function treeIndex(): TreeIndexConfiguration
    {
        return TreeIndexConfiguration::make(TreeNode::class);
    }

    public static function treeIndexWithInspector(): TreeIndexConfiguration
    {
        return TreeIndexConfiguration::make(TreeNode::class)
            ->forwardFromResource(self::class)
            ->inspectorPage(TestTreeInspectorPage::class);
    }
}
