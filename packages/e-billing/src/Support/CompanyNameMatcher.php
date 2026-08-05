<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Moox\Company\Models\Company;

final class CompanyNameMatcher
{
    public function match(?string $name): ?Company
    {
        $normalized = $this->normalizeName($name ?? '');

        if ($normalized === '') {
            return null;
        }

        $matches = Company::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [$normalized])
            ->get();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    private function normalizeName(string $value): string
    {
        $trimmed = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

        return mb_strtolower($trimmed);
    }
}
