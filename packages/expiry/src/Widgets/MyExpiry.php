<?php

namespace Moox\Expiry\Widgets;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Config;
use Livewire\WithPagination;
use Moox\Expiry\Models\Expiry;
use Override;

class MyExpiry extends BaseWidget
{
    use WithPagination;

    public $activeTab = 'all';

    protected string $view = 'expiry::widgets.my-expiry';

    protected int|string|array $columnSpan = [
        'default' => 1, // full width for default
        'lg' => 2,      // full width for large screens
    ];

    public function mount(): void
    {
        $this->activeTab = request('activeTab', 'all');
    }

    public function switchTab($tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function table(Table $table): Table
    {
        $tabsConfig = Config::get('expiry.expiry.tabs', []);
        $query = Expiry::query()->where('notified_to', auth()->id())->where('done_at', null);

        if (isset($tabsConfig[$this->activeTab]) && $tabsConfig[$this->activeTab]['value'] !== '') {
            $tabConfig = $tabsConfig[$this->activeTab];
            $query = $query->where($tabConfig['field'], $tabConfig['value']);
        }

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('title')
                    ->toggleable()
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                TextColumn::make('expired_at')
                    ->toggleable()
                    ->sortable()
                    ->since(),
                TextColumn::make('expiry_job')
                    ->toggleable()
                    ->sortable()
                    ->searchable()
                    ->limit(50),
                TextColumn::make('category')
                    ->toggleable()
                    ->sortable()
                    ->searchable()
                    ->limit(50),
                TextColumn::make('status')
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
            ->recordActions([
                ViewAction::make()->url(fn ($record): string => $record->link)
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    public function getTabs(): array
    {
        $tabsConfig = Config::get('expiry.expiry.tabs', []);
        $tabs = [];

        foreach ($tabsConfig as $key => $tabConfig) {
            $query = Expiry::query()->where('notified_to', auth()->id())->where('done_at', null);
            if ($tabConfig['value'] !== '') {
                $query = $query->where($tabConfig['field'], $tabConfig['value']);
            }

            $badgeCount = $query->count();
            $tabs[$key] = (object) [
                'label' => $tabConfig['label'],
                'icon' => $tabConfig['icon'],
                'badge' => $badgeCount,
                'key' => $key,
            ];
        }

        return $tabs;
    }

    #[Override]
    public function render(): View
    {
        return view('expiry::widgets.my-expiry', [
            'tabs' => $this->getTabs(),
            'activeTab' => $this->activeTab,
        ]);
    }

    public function resetPage($pageName = null): void
    {
        $this->setPage(1, $pageName);
    }
}
