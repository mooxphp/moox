<?php

declare(strict_types=1);

namespace Moox\Contact\Resources;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Moox\Contact\Models\ContactRelationshipType;
use Moox\Contact\Resources\ContactRelationshipType\Pages\CreateContactRelationshipType;
use Moox\Contact\Resources\ContactRelationshipType\Pages\EditContactRelationshipType;
use Moox\Contact\Resources\ContactRelationshipType\Pages\ListContactRelationshipTypes;
use Moox\Contact\Resources\ContactRelationshipType\Pages\ViewContactRelationshipType;
use Moox\Support\Audit\HasAuditForm;
use Moox\Support\Identity\HasIdentityForm;
use Moox\Support\Title\HasTitleForm;

class ContactRelationshipTypeResource extends Resource
{
    use HasAuditForm;
    use HasIdentityForm;
    use HasTitleForm;

    protected static ?string $model = ContactRelationshipType::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-category';

    public static function getModelLabel(): string
    {
        return __('contact::contact.contact_relationship_type');
    }

    public static function getPluralModelLabel(): string
    {
        return __('contact::contact.contact_relationship_types');
    }

    public static function getNavigationLabel(): string
    {
        return __('contact::contact.contact_relationship_types');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('translations');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make()
                ->schema([
                    Grid::make(1)
                        ->schema([
                            static::titleSection(),
                        ])
                        ->columnSpan(2),
                    Grid::make(1)
                        ->schema([
                            static::identitySection()->columns(1),
                            static::auditSection()->columns(1),
                        ])
                        ->columnSpan(1),
                ])
                ->columns(3)
                ->columnSpanFull(),
        ]);
    }

    protected static function featureTranslation(): string
    {
        return 'contact::fields';
    }

    /**
     * @return list<string|ValidationRule>
     */
    protected static function featureRules(string $field): array
    {
        return match ($field) {
            'title', 'slug' => ['required', 'string', 'max:255'],
            default => [],
        };
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ...static::titleColumns(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContactRelationshipTypes::route('/'),
            'create' => CreateContactRelationshipType::route('/create'),
            'view' => ViewContactRelationshipType::route('/{record}'),
            'edit' => EditContactRelationshipType::route('/{record}/edit'),
        ];
    }
}
