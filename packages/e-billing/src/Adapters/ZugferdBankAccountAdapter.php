<?php

declare(strict_types=1);

namespace Moox\EBilling\Adapters;

use Moox\Invoice\Support\En16931\BankAccount;
use Moox\Zugferd\Contracts\ZugferdBankAccount;

final class ZugferdBankAccountAdapter implements ZugferdBankAccount
{
    public function __construct(
        private BankAccount $account,
    ) {
    }

    public string $iban {
        get => $this->account->iban;
    }

    public ?string $bic {
        get => $this->account->bic;
    }

    public ?string $bankName {
        get => $this->account->bank_name;
    }

    public ?string $accountHolder {
        get => $this->account->account_holder;
    }
}
