<?php

declare(strict_types=1);

namespace Moox\Builder\Resources\FieldGroupResource\Pages\Concerns;

use Illuminate\Validation\ValidationException;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Services\FieldGroupPersistence;
use Moox\Builder\Support\FieldGroupSaveNotifier;

trait PersistsFieldGroupInAdmin
{
    /**
     * @param  array<string, mixed>  $data
     */
    protected function persistFieldGroup(FieldGroup $group, array $data): FieldGroup
    {
        try {
            app(FieldGroupPersistence::class)->sync($group, $data);
        } catch (ValidationException $exception) {
            FieldGroupSaveNotifier::notify($exception);

            throw $exception;
        }

        return $group->fresh(['fields.options', 'translations']);
    }
}
