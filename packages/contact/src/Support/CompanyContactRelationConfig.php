<?php

declare(strict_types=1);

namespace Moox\Contact\Support;

use Moox\Company\Models\Company;
use Moox\Contact\Models\CompanyContact;

final class CompanyContactRelationConfig
{
    public static function config(): array
    {
        /** @var array<string, mixed> $config */
        $config = config('contact.relations.companies', []);

        return $config;
    }

    public static function relationshipName(): string
    {
        return (string) (self::config()['relationship'] ?? 'companies');
    }

    public static function inverseRelationshipName(): string
    {
        return (string) (self::config()['inverse_relationship'] ?? 'contacts');
    }

    public static function pivotTable(): string
    {
        return (string) (self::config()['pivot_table'] ?? 'company_contact');
    }

    /** @return class-string */
    public static function relatedModel(): string
    {
        return (string) (self::config()['model'] ?? Company::class);
    }

    /** @return class-string */
    public static function pivotModel(): string
    {
        return (string) (self::config()['pivot_model'] ?? CompanyContact::class);
    }

    public static function contactForeignKey(): string
    {
        return (string) (self::config()['foreign_key'] ?? 'contact_id');
    }

    public static function companyForeignKey(): string
    {
        return (string) (self::config()['related_key'] ?? 'company_id');
    }

    /** @return list<string> */
    public static function pivotColumns(): array
    {
        /** @var list<string> $columns */
        $columns = self::config()['pivot_columns'] ?? ['role', 'is_primary'];

        return $columns;
    }

    /** @return list<string> */
    public static function roles(): array
    {
        /** @var list<string> $roles */
        $roles = config('contact.company_roles', ['general']);

        return $roles;
    }

    /** @return array<string, string> */
    public static function roleOptions(): array
    {
        return collect(self::roles())
            ->mapWithKeys(fn (string $role): array => [$role => __("contact::roles.{$role}")])
            ->all();
    }

    public static function label(bool $inverse = false): string
    {
        $key = $inverse
            ? (self::config()['inverse_label'] ?? 'contact::fields.contacts')
            : (self::config()['label'] ?? 'contact::fields.companies');

        return self::translateLabel((string) $key);
    }

    /** @return class-string|null */
    public static function relatedResource(bool $inverse = false): ?string
    {
        $key = $inverse ? 'inverse_related_resource' : 'related_resource';
        $resource = self::config()[$key] ?? null;

        return is_string($resource) && $resource !== '' && class_exists($resource) ? $resource : null;
    }

    public static function translateLabel(string $label): string
    {
        if (str_starts_with($label, 'trans//')) {
            $label = substr($label, 8);
        }

        return __($label);
    }
}
