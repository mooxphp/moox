<?php

declare(strict_types=1);

namespace Moox\Contact\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use InvalidArgumentException;
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
        /** @var mixed $configured */
        $configured = self::config()['pivot_table'] ?? null;

        return self::resolveNonEmptyString($configured, 'company_contact', 'contact.relations.companies.pivot_table');
    }

    /** @return class-string */
    public static function relatedModel(): string
    {
        /** @var mixed $configured */
        $configured = self::config()['model'] ?? null;

        return self::resolveModelClass(
            $configured,
            'Moox\\Company\\Models\\Company',
            'contact.relations.companies.model',
        );
    }

    /** @return class-string */
    public static function inverseRelatedModel(): string
    {
        /** @var mixed $configured */
        $configured = self::config()['inverse_model'] ?? null;

        return self::resolveModelClass(
            $configured,
            'Moox\\Contact\\Models\\Contact',
            'contact.relations.companies.inverse_model',
        );
    }

    /** @return class-string */
    public static function pivotModel(): string
    {
        /** @var mixed $configured */
        $configured = self::config()['pivot_model'] ?? null;
        $class = is_string($configured) && $configured !== '' ? $configured : CompanyContact::class;

        if (! class_exists($class)) {
            throw new InvalidArgumentException("Configured class for [contact.relations.companies.pivot_model] does not exist: {$class}");
        }

        if (! is_a($class, Pivot::class, true)) {
            throw new InvalidArgumentException("Configured class for [contact.relations.companies.pivot_model] must extend ".Pivot::class.": {$class}");
        }

        return $class;
    }

    public static function contactForeignKey(): string
    {
        /** @var mixed $configured */
        $configured = self::config()['foreign_key'] ?? null;

        return self::resolveNonEmptyString($configured, 'contact_id', 'contact.relations.companies.foreign_key');
    }

    public static function companyForeignKey(): string
    {
        /** @var mixed $configured */
        $configured = self::config()['related_key'] ?? null;

        return self::resolveNonEmptyString($configured, 'company_id', 'contact.relations.companies.related_key');
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

    /** @return class-string */
    private static function resolveModelClass(mixed $configured, string $fallback, string $configKey): string
    {
        $class = is_string($configured) && $configured !== '' ? $configured : $fallback;

        if (! class_exists($class)) {
            throw new InvalidArgumentException("Configured class for [{$configKey}] does not exist: {$class}");
        }

        if (! is_a($class, Model::class, true)) {
            throw new InvalidArgumentException("Configured class for [{$configKey}] must extend ".Model::class.": {$class}");
        }

        return $class;
    }

    private static function resolveNonEmptyString(mixed $configured, string $fallback, string $configKey): string
    {
        $value = is_string($configured) && trim($configured) !== '' ? trim($configured) : $fallback;

        if ($value === '') {
            throw new InvalidArgumentException("Configured value for [{$configKey}] must be a non-empty string.");
        }

        return $value;
    }
}
