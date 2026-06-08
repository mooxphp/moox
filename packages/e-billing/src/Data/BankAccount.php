<?php

declare(strict_types=1);

namespace Moox\EBilling\Data;

use Moox\Zugferd\Contracts\ZugferdBankAccount;

class BankAccount implements ZugferdBankAccount
{
    public function __construct(
        public string $iban,
        public ?string $bic = null,
        public ?string $bankName = null,
        public ?string $accountHolder = null,
    ) {}

    /**
     * @param  array{bank_name?: string, iban?: string, bic?: string, is_default?: bool, account_holder?: string}  $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            iban: is_string($row['iban'] ?? null) ? $row['iban'] : '',
            bic: isset($row['bic']) && is_string($row['bic']) ? $row['bic'] : null,
            bankName: isset($row['bank_name']) && is_string($row['bank_name']) ? $row['bank_name'] : null,
            accountHolder: isset($row['account_holder']) && is_string($row['account_holder']) ? $row['account_holder'] : null,
        );
    }

    /**
     * @return array{bank_name: ?string, iban: string, bic: ?string, is_default?: bool, account_holder: ?string}
     */
    public function toArray(): array
    {
        return array_filter([
            'bank_name' => $this->bankName,
            'iban' => $this->iban,
            'bic' => $this->bic,
            'account_holder' => $this->accountHolder,
        ], fn (mixed $value): bool => $value !== null);
    }
}
