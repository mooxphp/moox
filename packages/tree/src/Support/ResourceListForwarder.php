<?php

declare(strict_types=1);

namespace Moox\Tree\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class ResourceListForwarder
{
    /**
     * @param  class-string  $resourceClass
     */
    public static function baseQuery(string $resourceClass): Builder
    {
        if (! method_exists($resourceClass, 'getEloquentQuery')) {
            return $resourceClass::getModel()::query();
        }

        return $resourceClass::getEloquentQuery();
    }

    /**
     * Apply the same search scope as the resource list table, without requiring a mounted Filament column.
     *
     * @param  class-string  $resourceClass
     */
    public static function applySearch(string $resourceClass, Builder $query, string $search, string $lang): Builder
    {
        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        static::syncLanguage($lang);

        if (method_exists($resourceClass, 'applyListSearchToQuery')) {
            return $resourceClass::applyListSearchToQuery($query, $search);
        }

        if (! method_exists($resourceClass, 'getTitleColumn')) {
            return $query;
        }

        $column = $resourceClass::getTitleColumn();

        if (! $column->isGloballySearchable()) {
            return $query;
        }

        $resolvedLang = $lang !== '' ? $lang : (string) request()->input('lang', TreeLocale::resolveActiveLanguage());

        /** @var class-string<Model> $modelClass */
        $modelClass = $resourceClass::getModel();
        $model = new $modelClass;

        if (method_exists($model, 'translations')) {
            return $query->whereHas('translations', function (Builder $translationQuery) use ($search, $resolvedLang): void {
                $translationQuery
                    ->where('locale', $resolvedLang)
                    ->where('title', 'like', '%'.$search.'%');
            });
        }

        $columnName = $column->getName();

        if ($columnName === '') {
            return $query;
        }

        return $query->where($columnName, 'like', '%'.$search.'%');
    }

    /**
     * Sync locale like list pages; does not narrow the tree query (same as table list).
     *
     * @param  class-string  $resourceClass
     */
    public static function applyLanguage(string $resourceClass, Builder $query, string $lang): Builder
    {
        static::syncLanguage($lang);

        return $query;
    }

    private static function syncLanguage(string $lang): void
    {
        $lang = trim($lang);

        if ($lang === '') {
            return;
        }

        request()->merge(['lang' => $lang]);
        TreeLocale::syncApplicationLocale($lang);
    }
}
