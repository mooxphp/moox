<?php

declare(strict_types=1);

namespace Moox\Media\Resources\MediaCollectionResource\Pages;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Entities\Items\Static\Pages\BaseEditStaticRecord;
use Moox\Media\Resources\MediaCollectionResource;
use Override;

class EditMediaCollection extends BaseEditStaticRecord
{
    protected static string $resource = MediaCollectionResource::class;

    /**
     * Media collections use locale_variant as translation locale (e.g. de_DE).
     */
    public function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        if (! method_exists($record, 'getTranslation') || ! property_exists($record, 'translatedAttributes')) {
            return $data;
        }

        $translation = $record->getTranslation($this->lang, false);

        if (! $translation) {
            foreach ($record->translatedAttributes as $attribute) {
                $data[$attribute] = null;
            }

            return $data;
        }

        foreach ($record->translatedAttributes as $attribute) {
            $data[$attribute] = $translation->$attribute ?? null;
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! method_exists($record, 'translateOrNew') || ! property_exists($record, 'translatedAttributes')) {
            $record->update($data);

            return $record;
        }

        $translation = $record->translateOrNew($this->lang);

        foreach ($record->translatedAttributes as $attribute) {
            if (array_key_exists($attribute, $data)) {
                $translation->setAttribute($attribute, $data[$attribute]);
                unset($data[$attribute]);
            }
        }

        $record->update($data);
        $record->save();

        return $record;
    }

    #[Override]
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index', ['lang' => $this->lang]);
    }
}
