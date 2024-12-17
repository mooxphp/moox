<?php

namespace Moox\Expiry\Resources;

use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\SoftDelete\SingleSoftDeleteInResource;
use Moox\Core\Traits\Tabs\TabsInResource;
use Moox\Expiry\Actions\CustomExpiryAction;
use Moox\Expiry\Models\Expiry;
use Moox\Expiry\Resources\ExpiryResource\Pages;

class ExpiryResource extends Resource
{
    use BaseInResource, SingleSoftDeleteInResource, TabsInResource;

    protected static ?string $model = Expiry::class;

    protected static ?string $navigationIcon = 'gmdi-access-time-o';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Form $form): Form
    {
        return $form->schema([

        ]);
    }

    public static function table(Table $table): Table
    {
        $expiryActionClass = config('expiry.expiry_action');

        if (empty($expiryActionClass)) {
            $expiryActionClass = CustomExpiryAction::class;
        }

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label(__('core::core.title'))
                    ->toggleable()
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('expired_at')
                    ->label(__('core::expiry.expired_at'))
                    ->toggleable()
                    ->sortable()
                    ->since(),
                Tables\Columns\TextColumn::make('processing_deadline')
                    ->label(__('core::expiry.processing_deadline'))
                    ->toggleable()
                    ->sortable()
                    ->since()
                    ->hidden(fn () => ! Expiry::query()->whereNotNull('processing_deadline')->exists())
                    ->visible(
                        fn ($livewire) => isset($livewire->activeTab)
                        && in_array($livewire->activeTab, ['all', 'documents', 'tasks'])),
                Tables\Columns\TextColumn::make('escalated_at')
                    ->label(__('core::expiry.escalated_at'))
                    ->toggleable()
                    ->sortable()
                    ->date()
                    ->icon('gmdi-warning')
                    ->color('warning')
                    ->hidden(fn () => ! Expiry::query()->whereNotNull('escalated_at')->exists())
                    ->visible(
                        fn ($livewire) => isset($livewire->activeTab)
                        && in_array($livewire->activeTab, ['all', 'documents', 'tasks'])),
                Tables\Columns\TextColumn::make('cycle')
                    ->label(__('core::expiry.cycle'))
                    ->toggleable()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('notifyUser.display_name')
                    ->label(__('core::expiry.notifyUser'))
                    ->toggleable()
                    ->searchable()
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        $wpPrefix = config('press.wordpress_prefix');

                        $tableName = $wpPrefix.'users';

                        return $query
                            ->leftJoin($tableName, 'expiries.notified_to', '=', "{$tableName}.ID")
                            ->orderBy("{$tableName}.display_name", $direction)
                            ->select('expiries.*');
                    })
                    ->limit(50),
                Tables\Columns\TextColumn::make('expiry_job')
                    ->label(__('core::expiry.expiry_job'))
                    ->toggleable()
                    ->sortable()
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('category')
                    ->label(__('core::core.category'))
                    ->toggleable()
                    ->sortable()
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('core::core.status'))
                    ->toggleable()
                    ->sortable()
                    ->searchable()
                    ->limit(50),
            ])
            ->defaultSort('escalated_at', 'desc')
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

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExpiries::route('/'),
            'create' => Pages\CreateExpiry::route('/create'),
            'view' => Pages\ViewExpiry::route('/{record}'),
            'edit' => Pages\EditExpiry::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return config('expiry.resources.expiry.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('expiry.resources.expiry.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('expiry.resources.expiry.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('expiry.resources.expiry.single');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('expiry.navigation_group');
    }

    public static function getNavigationSort(): ?int
    {
        return config('expiry.navigation_sort') + 1;
    }
}
