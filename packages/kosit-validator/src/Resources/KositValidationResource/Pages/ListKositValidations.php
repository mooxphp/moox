<?php

declare(strict_types=1);

namespace Moox\KositValidator\Resources\KositValidationResource\Pages;

use Closure;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Moox\Core\Traits\Base\BaseInListPage;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\KositValidator\Models\KositValidation;
use Moox\KositValidator\Resources\KositValidationResource;
use Moox\KositValidator\Support\KositValidationMessages;

final class ListKositValidations extends ListRecords
{
    use BaseInListPage;
    use HasListPageTabs;

    protected static string $resource = KositValidationResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountTabsInListPage();
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('kosit-validator.resources.kosit-validation.tabs', KositValidation::class);
    }

    /**
     * @param  Builder<KositValidation>  $query
     * @param  list<array{field: string, operator: string, value: mixed}>  $conditions
     * @return Builder<KositValidation>
     */
    protected function applyConditions($query, $conditions)
    {
        foreach ($conditions as $condition) {
            if ($condition['field'] === '__has_message_type') {
                $query = KositValidationMessages::applyHasMessageType($query, (string) $condition['value']);

                continue;
            }

            $value = $condition['value'];

            if ($value instanceof Closure) {
                $value = $value();
            }

            $query = $query->where($condition['field'], $condition['operator'], $value);
        }

        return $query;
    }
}
