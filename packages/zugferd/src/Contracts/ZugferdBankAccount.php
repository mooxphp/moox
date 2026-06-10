<?php

declare(strict_types=1);

namespace Moox\Zugferd\Contracts;

interface ZugferdBankAccount
{
    public string $iban { get; }

    public ?string $bic { get; }

    public ?string $bankName { get; }

    public ?string $accountHolder { get; }
}
