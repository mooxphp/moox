<?php

declare(strict_types=1);

namespace Moox\Address\Resources\Address\Pages;

use Illuminate\Database\Eloquent\Model;
use Moox\Address\Exceptions\DuplicateAddressException;
use Moox\Address\Resources\Address\Pages\Concerns\HandlesDuplicateAddressValidation;
use Moox\Address\Resources\Address\Pages\Concerns\InitializesValidationBag;
use Moox\Address\Resources\AddressResource;
use Moox\Core\Entities\Items\Record\Pages\BaseEditRecord;

class EditAddress extends BaseEditRecord
{
    use HandlesDuplicateAddressValidation;
    use InitializesValidationBag;

    protected static string $resource = AddressResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return parent::handleRecordUpdate($record, $data);
        } catch (DuplicateAddressException $exception) {
            throw $this->duplicateAddressExceptionForForm($exception);
        }
    }
}
