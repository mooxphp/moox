<?php

namespace Moox\Expiry\Widgets;

use Filament\Resources\Components\Tab;
use Filament\Tables;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Config;
use Moox\Expiry\Models\Expiry;

class MyExpiry extends BaseWidget
{
    protected int|string|array $columnSpan = [
        'sm' => 3,
        'md' => 6,
        'xl' => 12,
    ];

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Expiry::query()->where('notified_to', auth()->id())->where('done_at', null);
    }

    public function table(Table $table): Table
    {
        $activeTab = request('activeTab', 'all');
        $tabsConfig = Config::get('expiry.tabs', []);
        $query = $this->getTableQuery();

        if (isset($tabsConfig[$activeTab]) && $tabsConfig[$activeTab]['value'] !== '') {
            $tabConfig = $tabsConfig[$activeTab];
            $query = $query->where($tabConfig['field'], $tabConfig['value']);
        }

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->toggleable()
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('expired_at')
                    ->toggleable()
                    ->sortable()
                    ->since(),
                Tables\Columns\TextColumn::make('expiry_job')
                    ->toggleable()
                    ->sortable()
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('category')
                    ->toggleable()
                    ->sortable()
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('status')
                    ->toggleable()
                    ->sortable()
                    ->searchable()
                    ->limit(50),
            ])
            ->filters([
                SelectFilter::make('expiry_job')
                    ->label('Job')
                    ->options(Expiry::getExpiryJobOptions()),

                SelectFilter::make('category')
                    ->label('Category')
                    ->options(Expiry::getExpiryCategoryOptions()),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(Expiry::getExpiryStatusOptions()),
            ])
            ->actions([
                ViewAction::make()->url(fn ($record): string => "{$record->link}")
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public function getTabs(): array
    {
        $tabsConfig = Config::get('expiry.tabs', []);
        $tabs = [];

        foreach ($tabsConfig as $key => $tabConfig) {
            $tabs[$key] = Tab::make($tabConfig['label'])
                ->modifyQueryUsing(fn ($query) => $query->where($tabConfig['field'], $tabConfig['value']))
                ->badge(Expiry::query()->where($tabConfig['field'], $tabConfig['value'])->count())
                ->icon($tabConfig['icon']);
        }

        return $tabs;
    }
}
