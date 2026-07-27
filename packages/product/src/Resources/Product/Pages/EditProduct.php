<?php

declare(strict_types=1);

namespace Moox\Product\Resources\Product\Pages;

use Moox\Core\Entities\Items\Draft\Pages\BaseEditDraft;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Localization\Models\Localization;
use Moox\Product\Models\Product;
use Moox\Product\Resources\ProductResource;

class EditProduct extends BaseEditDraft
{
    use HasListPageTabs;

    protected static string $resource = ProductResource::class;

    public function getTitle(): string
    {
        $entity = $this->getResource()::getModelLabel();

        $translation = $this->record->translations()->where('locale', $this->lang)->first();

        if ($translation && $translation->name) {
            return $entity.' - '.$translation->name;
        }

        return $entity.' - '.__('core::core.translation_create');
    }

    public function getHeading(): string
    {
        $entity = $this->getResource()::getModelLabel();

        $translation = $this->record->translations()->where('locale', $this->lang)->first();

        if ($translation && $translation->name) {
            return $entity.' - '.$translation->name;
        }

        $heading = $entity.' - '.__('core::core.translation_create');

        $defaultLocalization = Localization::where('is_default', true)->first();
        $defaultLang = $defaultLocalization->locale_variant ?? app()->getLocale();
        $fallbackTranslation = $this->record->translations()->where('locale', $defaultLang)->first();

        if ($fallbackTranslation && $fallbackTranslation->name) {
            $heading .= ' ('.$fallbackTranslation->name.' - '.$defaultLang.')';
        } else {
            $anyTranslation = $this->record->translations()->whereNotNull('name')->first();
            if ($anyTranslation && $anyTranslation->name) {
                $heading .= ' ('.$anyTranslation->name.' - '.$anyTranslation->locale.')';
            }
        }

        return $heading;
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('product.resources.product.tabs', Product::class);
    }
}
