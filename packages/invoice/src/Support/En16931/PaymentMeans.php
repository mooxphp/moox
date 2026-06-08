<?php

declare(strict_types=1);

namespace Moox\Invoice\Support\En16931;

readonly class PaymentMeans
{
    /**
     * @param  list<BankAccount>  $bank_accounts
     */
    public function __construct(
        public ?string $payment_means_code,
        public array $bank_accounts,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $bankAccounts = [];

        foreach ($data['bank_accounts'] ?? [] as $row) {
            if (is_array($row)) {
                $bankAccounts[] = BankAccount::fromArray($row);
            }
        }

        $paymentMeansCode = isset($data['payment_means_code'])
            ? (string) $data['payment_means_code']
            : null;

        return new self(
            payment_means_code: $paymentMeansCode,
            bank_accounts: $bankAccounts,
        );
    }

    /**
     * @return list<BankAccount>
     */
    public function bankAccounts(): array
    {
        return $this->bank_accounts;
    }

    /**
     * @return array{payment_means_code: ?string, bank_accounts: list<array{iban: string, bic: ?string, bank_name: ?string, account_holder: ?string}>}
     */
    public function toArray(): array
    {
        return [
            'payment_means_code' => $this->payment_means_code,
            'bank_accounts' => array_map(
                fn (BankAccount $account): array => $account->toArray(),
                $this->bank_accounts,
            ),
        ];
    }
}
