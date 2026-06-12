<?php

declare(strict_types=1);

namespace Moox\Invoice\Support\En16931;

readonly class BankAccount
{
    public function __construct(
        public string $iban,
        public ?string $bic,
        public ?string $bank_name,
        public ?string $account_holder,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            iban: (string) ($data['iban'] ?? ''),
            bic: isset($data['bic']) ? (string) $data['bic'] : null,
            bank_name: isset($data['bank_name']) ? (string) $data['bank_name'] : null,
            account_holder: isset($data['account_holder']) ? (string) $data['account_holder'] : null,
        );
    }

    /**
     * @return array{iban: string, bic: ?string, bank_name: ?string, account_holder: ?string}
     */
    public function toArray(): array
    {
        return [
            'iban' => $this->iban,
            'bic' => $this->bic,
            'bank_name' => $this->bank_name,
            'account_holder' => $this->account_holder,
        ];
    }
}
