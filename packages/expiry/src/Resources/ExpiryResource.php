<?php

namespace Moox\Expiry\Resources;

use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\SoftDelete\SingleSoftDeleteInResource;
use Moox\Core\Traits\Tabs\TabsInResource;
use Moox\Expiry\Actions\CustomExpiryAction;
use Moox\Expiry\Models\Expiry;
use Moox\Expiry\Resources\ExpiryResource\Pages\CreateExpiry;
use Moox\Expiry\Resources\ExpiryResource\Pages\EditExpiry;
use Moox\Expiry\Resources\ExpiryResource\Pages\ListExpiries;
use Moox\Expiry\Resources\ExpiryResource\Pages\ViewExpiry;
use Override;

class ExpiryResource extends Resource
{
    use BaseInResource;
    use SingleSoftDeleteInResource;
    use TabsInResource;

    protected static ?string $model = Expiry::class;

    protected static ?string $navigationIcon = 'gmdi-view-timeline-o';

    protected static ?string $recordTitleAttribute = 'title';

    #[Override]
    public static function form(Form $form): Form
    {
        return $form->schema([

        ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        $expiryActionClass = config('expiry.expiry_action');

        if (empty($expiryActionClass)) {
            $expiryActionClass = CustomExpiryAction::class;
        }

        return $table
            ->query(
                Expiry::query()
                    ->orderBy('escalated_at', 'desc')
                    ->orderBy('expired_at', 'asc')
            )
            ->columns([
                TextColumn::make('title')
                    ->label(__('core::core.title'))
                    ->toggleable()
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                TextColumn::make('expired_at')
                    ->label(__('core::expiry.expired_at'))
                    ->toggleable()
                    ->sortable()
                    ->since(),
                TextColumn::make('processing_deadline')
                    ->label(__('core::expiry.processing_deadline'))
                    ->toggleable()
                    ->sortable()
                    ->since()
                    ->hidden(fn (): bool => ! Expiry::query()->whereNotNull('processing_deadline')->exists())
                    ->visible(
                        fn ($livewire): bool => isset($livewire->activeTab)
                        && in_array($livewire->activeTab, ['all', 'documents', 'tasks'])),
                TextColumn::make('escalated_at')
                    ->label(__('core::expiry.escalated_at'))
                    ->toggleable()
                    ->sortable()
                    ->date()
                    ->icon('gmdi-warning')
                    ->color('warning')
                    ->hidden(fn (): bool => ! Expiry::query()->whereNotNull('escalated_at')->exists())
                    ->visible(
                        fn ($livewire): bool => isset($livewire->activeTab)
                        && in_array($livewire->activeTab, ['all', 'documents', 'tasks'])),
                TextColumn::make('cycle')
                    ->label(__('core::expiry.cycle'))
                    ->toggleable()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('notifyUser.display_name')
                    ->label(__('core::expiry.notifyUser'))
                    ->toggleable()
                    ->searchable()
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        $wpPrefix = config('press.wordpress_prefix');

                        $tableName = $wpPrefix.'users';

                        return $query
                            ->leftJoin($tableName, 'expiries.notified_to', '=', $tableName.'.ID')
                            ->orderBy($tableName.'.display_name', $direction)
                            ->select('expiries.*');
                    })
                    ->limit(50),
                TextColumn::make('expiry_job')
                    ->label(__('core::expiry.expiry_job'))
                    ->toggleable()
                    ->sortable()
                    ->searchable()
                    ->limit(50),
                TextColumn::make('category')
                    ->label(__('core::core.category'))
                    ->toggleable()
                    ->sortable()
                    ->searchable()
                    ->limit(50),
                TextColumn::make('status')
                    ->label(__('core::core.status'))
                    ->toggleable()
                    ->sortable()
                    ->searchable()
                    ->limit(50),
            ])
            ->filters([
                SelectFilter::make('expiry_job')
                    ->label(__('core::expiry.expiry_job'))
                    ->options(Expiry::getExpiryJobOptions()),

                SelectFilter::make('category')
                    ->label(__('core::core.category'))
                    ->options(Expiry::getExpiryCategoryOptions()),

                SelectFilter::make('status')
                    ->label(__('core::core.status'))
                    ->options(Expiry::getExpiryStatusOptions()),

                SelectFilter::make('notified_to')
                    ->label(__('core::expiry.notifyUser'))
                    ->options(Expiry::getUserOptions()),
            ])
            ->actions([
                $expiryActionClass::make(),

                ViewAction::make()
                    ->url(function ($record) {
                        if (config('expiry.url_patterns.enabled')) {
                            $patterns = config('expiry.url_patterns.patterns');
                            $category = $record->category;

                            return $record->link.($patterns[$category] ?? $patterns['default']);
                        } else {
                            return $record->link;
                        }
                    })
                    ->color(config('expiry.expiry_view_action_color'))
                    ->openUrlInNewTab(),

            ])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    #[Override]
    public static function getRelations(): array
    {
        return [];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListExpiries::route('/'),
            'create' => CreateExpiry::route('/create'),
            'view' => ViewExpiry::route('/{record}'),
            'edit' => EditExpiry::route('/{record}/edit'),
        ];
    }

    #[Override]
    public static function getModelLabel(): string
    {
        return config('expiry.resources.expiry.single');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return config('expiry.resources.expiry.plural');
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return config('expiry.resources.expiry.plural');
    }

    #[Override]
    public static function getBreadcrumb(): string
    {
        return config('expiry.resources.expiry.single');
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return config('expiry.navigation_group');
    }

    #[Override]
    public static function getNavigationSort(): ?int
    {
        return config('expiry.navigation_sort') + 1;
    }
}
