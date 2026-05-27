<?php

declare(strict_types=1);

namespace Moox\Address\Resources\Address\Pages\Concerns;

use Illuminate\Validation\ValidationException;
use Moox\Address\Exceptions\DuplicateAddressException;

trait HandlesDuplicateAddressValidation
{
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
