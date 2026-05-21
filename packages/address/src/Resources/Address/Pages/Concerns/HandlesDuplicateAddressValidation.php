<?php

declare(strict_types=1);

namespace Moox\Address\Resources\Address\Pages\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Moox\Address\Exceptions\DuplicateAddressException;

trait HandlesDuplicateAddressValidation
{
    protected function handleRecordCreation(array $data): Model
    {
        try {
            return parent::handleRecordCreation($data);
        } catch (DuplicateAddressException $exception) {
            throw $this->duplicateAddressExceptionForForm($exception);
        }
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return parent::handleRecordUpdate($record, $data);
        } catch (DuplicateAddressException $exception) {
            throw $this->duplicateAddressExceptionForForm($exception);
        }
    }

    protected function duplicateAddressExceptionForForm(DuplicateAddressException $exception): ValidationException
    {
        $statePath = $this->form->getStatePath();

        $messages = [];

        foreach ($exception->errors() as $key => $fieldMessages) {
            $formKey = filled($statePath) ? "{$statePath}.{$key}" : $key;

            $messages[$formKey] = $fieldMessages;
        }

        return ValidationException::withMessages($messages);
    }
}
