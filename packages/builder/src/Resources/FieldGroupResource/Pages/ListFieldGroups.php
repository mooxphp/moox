<?php

declare(strict_types=1);

namespace Moox\Builder\Resources\FieldGroupResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Moox\Builder\Filament\Actions\FieldGroupDefinitionActions;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Resources\FieldGroupResource;
use Moox\Builder\Resources\FieldGroupResource\Pages\Concerns\InteractsWithFieldGroupLocale;
use Moox\Builder\Support\BuilderLocaleResolver;

class ListFieldGroups extends ListRecords
{
    use InteractsWithFieldGroupLocale;

    protected static string $resource = FieldGroupResource::class;

    /** @var array<string, array<string, string>> */
    protected $queryString = [
        'lang' => ['except' => ''],
    ];

    public function hydrate(): void
    {
        $this->hydrateInteractsWithFieldGroupLocale();
    }

    public function mount(): void
    {
        parent::mount();

        $this->mountInteractsWithFieldGroupLocale();
    }

    /**
     * @return Builder<FieldGroup>
     */
    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()->with('translations');
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getFieldGroupLanguageSelectorAction(),
            FieldGroupDefinitionActions::import($this, $this->lang !== '' ? $this->lang : null),
            CreateAction::make()
                ->url(fn (): string => FieldGroupResource::getUrl('create', [
                    'lang' => $this->lang !== ''
                        ? $this->lang
                        : app(BuilderLocaleResolver::class)->adminDefaultLocale(),
                ])),
        ];
    }
}
