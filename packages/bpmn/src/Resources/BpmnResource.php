<?php

namespace Moox\Bpmn\Resources;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Moox\Bpmn\Models\Bpmn;
use Moox\Bpmn\Resources\Pages\CreateBpmn;
use Moox\Bpmn\Resources\Pages\EditBpmn;
use Moox\Bpmn\Resources\Pages\ListBpmns;
use Moox\Bpmn\Resources\Pages\ViewBpmn;
use Moox\Bpmn\Resources\Schemas\BpmnForm;
use Moox\Bpmn\Resources\Schemas\BpmnInfolist;
use Moox\Bpmn\Resources\Tables\BpmnsTable;

class BpmnResource extends Resource
{
    protected static ?string $model = Bpmn::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Bpmn';

    public static function form(Schema $schema): Schema
    {
        return BpmnForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BpmnInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BpmnsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBpmns::route('/'),
            'create' => CreateBpmn::route('/create'),
            'view' => ViewBpmn::route('/{record}'),
            'edit' => EditBpmn::route('/{record}/edit'),
        ];
    }
}
