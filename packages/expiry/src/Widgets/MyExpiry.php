<?php

namespace Moox\Expiry\Widgets;

use Filament\Tables;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Moox\Core\Base\BaseWidget;
use Moox\Expiry\Models\Expiry;

class MyExpiry extends BaseWidget
{
    protected int|string|array $columnSpan = [
        'sm' => 3,
        'md' => 6,
        'xl' => 12,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Expiry::query()->where('notified_to', auth()->id())->where('done_at', null),
            )

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

    public function getPresetViews(): array
    {
        if ($this->useAdvancedTables === true) {

            return [
                'Dokumente' => \Archilex\AdvancedTables\Components\PresetView::make()
                    ->modifyQueryUsing(fn ($query) => $query->where('expiry_job', 'Wiki Dokumente'))
                    ->icon('heroicon-o-document-text')
                    ->badge(Expiry::query()->where('expiry_job', 'Wiki Dokumente')->count())
                    ->favorite(),
                'Artikel' => \Archilex\AdvancedTables\Components\PresetView::make()
                    ->modifyQueryUsing(fn ($query) => $query->where('expiry_job', 'Wiki Artikel'))
                    ->icon('heroicon-o-document-check')
                    ->badge(Expiry::query()->where('expiry_job', 'Wiki Artikel')->count())
                    ->favorite(),
                'Aufgaben' => \Archilex\AdvancedTables\Components\PresetView::make()
                    ->modifyQueryUsing(fn ($query) => $query->where('expiry_job', 'Wiki Aufgaben'))
                    ->icon('heroicon-o-clipboard-document-list')
                    ->badge(Expiry::query()->where('expiry_job', 'Wiki Aufgaben')->count())
                    ->favorite(),
            ];
        }
    }
}
