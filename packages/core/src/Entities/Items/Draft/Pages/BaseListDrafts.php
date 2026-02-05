<?php

namespace Moox\Core\Entities\Items\Draft\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;
use Moox\Core\Entities\Items\Draft\BaseDraftModel;
use Moox\Core\Traits\CanResolveResourceClass;
use Moox\Localization\Models\Localization;

abstract class BaseListDrafts extends ListRecords
{
    use CanResolveResourceClass;

    public string $lang;

    protected $queryString = [
        'lang' => ['except' => ''],
    ];

    public function mount(): void
    {
        parent::mount();
        $defaultLocalization = Localization::where('is_default', true)->first();
        $defaultLang = $defaultLocalization->locale_variant ?? config('app.locale');

        $this->lang = request()->get('lang', $defaultLang);
    }

    public function getTitle(): string
    {
        if ($this->activeTab === 'deleted') {
            return parent::getTitle().' - '.__('core::core.trash');
        }

        return parent::getTitle();
    }

    /**
     * Get header actions for the list page
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(fn (array $data, string $model): Model => $model::create($data))
                ->hidden(fn (): bool => $this->activeTab === 'deleted'),
            Action::make('emptyTrash')
                ->label(__('core::core.empty_trash'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->action(function (): void {
                    /** @var class-string<BaseDraftModel> $model */
                    $model = $this->getModel();
                    $trashedCount = $model::onlyTrashed()->count();
                    $model::onlyTrashed()->forceDelete();
                    Notification::make()
                        ->title(__('core::core.trash_emptied_successfully'))
                        ->body(trans_choice('core::core.items_permanently_deleted', $trashedCount, ['count' => $trashedCount]))
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('index', ['lang' => $this->lang, 'tab' => 'all']));
                })
                ->requiresConfirmation()
                ->visible(function (): bool {
                    if ($this->activeTab !== 'deleted') {
                        return false;
                    }
                    /** @var class-string<BaseDraftModel> $model */
                    $model = $this->getModel();

                    return $model::onlyTrashed()->exists();
                }), ];
    }

    public function changeLanguage(string $lang): void
    {
        $this->lang = $lang;

        $url = $this->getResource()::getUrl('index', ['lang' => $lang, 'tab' => $this->activeTab]);

        $this->redirect($url);
    }
}
