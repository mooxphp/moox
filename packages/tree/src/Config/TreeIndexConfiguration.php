<?php

declare(strict_types=1);

namespace Moox\Tree\Config;

use Illuminate\Database\Eloquent\Builder;
use Moox\Tree\Support\ResourceListForwarder;
use Moox\Tree\Support\TreeIndexQueryBuilder;
use Moox\Tree\Support\TreeIndexResourcePages;
use Moox\Tree\Support\TreeLocale;

final class TreeIndexConfiguration
{
    /** @var (\Closure(Builder): Builder)|null */
    private readonly ?\Closure $modifyQuery;

    /** @var (\Closure(Builder, string, self): Builder)|null */
    private readonly ?\Closure $applySearchUsing;

    /** @var (\Closure(Builder, string, self): Builder)|null */
    private readonly ?\Closure $applyLanguageUsing;

    /**
     * @param  class-string|null  $sourceResourceClass
     * @param  class-string|null  $inspectorPageClass
     * @param  class-string|null  $inspectorCreatePageClass
     */
    private function __construct(
        private readonly string $modelClass,
        private readonly ?string $sourceResourceClass,
        private readonly string $parentColumn,
        private readonly string $sortColumn,
        private readonly string $labelColumn,
        private readonly bool $labelColumnQueryable,
        private readonly ?string $labelFallbackColumn,
        private readonly bool $nestedSet,
        private readonly bool $reorderable,
        private readonly ?string $inspectorPageClass,
        private readonly ?string $inspectorCreatePageClass,
        private readonly bool $stubCreate,
        private readonly ?string $authorizationAbility,
        private readonly string $treeHeading,
        private readonly string $treeSubheading,
        private readonly string $inspectorHeading,
        private readonly string $createRootLabel,
        private readonly string $saveLabel,
        private readonly string $newRecordLabel,
        private readonly string $deleteConfirmMessage,
        private readonly bool $toolbarSearchEnabled,
        private readonly bool $toolbarLanguageSwitcherEnabled,
        private readonly bool $filamentTableLanguageSwitcherEnabled,
        private readonly bool $useFilamentTableToolbar,
        ?\Closure $modifyQuery = null,
        ?\Closure $applySearchUsing = null,
        ?\Closure $applyLanguageUsing = null,
    ) {
        $this->modifyQuery = $modifyQuery;
        $this->applySearchUsing = $applySearchUsing;
        $this->applyLanguageUsing = $applyLanguageUsing;
    }

    public static function make(string $modelClass): self
    {
        return new self(
            modelClass: $modelClass,
            sourceResourceClass: null,
            parentColumn: 'parent_id',
            sortColumn: 'sort_order',
            labelColumn: 'label',
            labelColumnQueryable: true,
            labelFallbackColumn: 'display_title',
            nestedSet: false,
            reorderable: true,
            inspectorPageClass: null,
            inspectorCreatePageClass: null,
            stubCreate: false,
            authorizationAbility: null,
            treeHeading: 'Struktur',
            treeSubheading: 'Baum',
            inspectorHeading: 'Einstellungen',
            createRootLabel: 'Neuer Eintrag',
            saveLabel: 'Speichern',
            newRecordLabel: 'Neuer Eintrag',
            deleteConfirmMessage: 'Diesen Eintrag inklusive Untereinträge löschen?',
            toolbarSearchEnabled: false,
            toolbarLanguageSwitcherEnabled: false,
            filamentTableLanguageSwitcherEnabled: false,
            useFilamentTableToolbar: false,
        );
    }

    public function parentColumn(string $parentColumn): self
    {
        return $this->cloneWith(parentColumn: $parentColumn);
    }

    public function sortColumn(string $sortColumn): self
    {
        return $this->cloneWith(sortColumn: $sortColumn);
    }

    public function labelColumn(string $labelColumn): self
    {
        return $this->cloneWith(labelColumn: $labelColumn);
    }

    public function labelColumnQueryable(bool $labelColumnQueryable = true): self
    {
        return $this->cloneWith(labelColumnQueryable: $labelColumnQueryable);
    }

    public function labelFallbackColumn(?string $labelFallbackColumn): self
    {
        return $this->cloneWith(labelFallbackColumn: $labelFallbackColumn);
    }

    public function nestedSet(bool $nestedSet = true): self
    {
        return $this->cloneWith(nestedSet: $nestedSet);
    }

    public function authorizationAbility(?string $authorizationAbility): self
    {
        return $this->cloneWith(authorizationAbility: $authorizationAbility);
    }

    public function reorderable(bool $reorderable = true): self
    {
        return $this->cloneWith(reorderable: $reorderable);
    }

    /**
     * @param  class-string  $inspectorPageClass
     */
    public function inspectorPage(string $inspectorPageClass): self
    {
        return $this->cloneWith(inspectorPageClass: $inspectorPageClass);
    }

    /**
     * @param  class-string  $inspectorCreatePageClass
     */
    public function inspectorCreatePage(string $inspectorCreatePageClass): self
    {
        return $this->cloneWith(inspectorCreatePageClass: $inspectorCreatePageClass);
    }

    /**
     * Use the stub create flow (minimal label node + edit inspector) instead of the resource create form.
     */
    public function stubCreate(bool $enabled = true): self
    {
        return $this->cloneWith(stubCreate: $enabled);
    }

    /**
     * @return class-string|null
     */
    public function getInspectorPageClass(): ?string
    {
        return $this->inspectorPageClass;
    }

    /**
     * @return class-string|null
     */
    public function getInspectorCreatePageClass(): ?string
    {
        return $this->inspectorCreatePageClass;
    }

    public function usesResourceInspector(): bool
    {
        return $this->inspectorPageClass !== null;
    }

    public function usesResourceCreateInspector(): bool
    {
        if ($this->stubCreate) {
            return false;
        }

        if ($this->inspectorCreatePageClass !== null) {
            return true;
        }

        if (! $this->usesResourceInspector()) {
            return false;
        }

        return TreeIndexResourcePages::resolveCreatePageClass($this) !== null;
    }

    /**
     * @param  \Closure(Builder): Builder  $modifyQuery
     */
    public function modifyQuery(\Closure $modifyQuery): self
    {
        return $this->cloneWith(modifyQuery: $modifyQuery);
    }

    public function toolbarSearch(bool $enabled = true): self
    {
        return $this->cloneWith(toolbarSearchEnabled: $enabled);
    }

    public function toolbarLanguageSwitcher(bool $enabled = true): self
    {
        return $this->cloneWith(toolbarLanguageSwitcherEnabled: $enabled);
    }

    public function filamentTableLanguageSwitcher(bool $enabled = true): self
    {
        return $this->cloneWith(filamentTableLanguageSwitcherEnabled: $enabled);
    }

    /**
     * @param  \Closure(Builder, string, self): Builder  $callback
     */
    public function applySearchUsing(\Closure $callback): self
    {
        return $this->cloneWith(applySearchUsing: $callback);
    }

    /**
     * @param  \Closure(Builder, string, self): Builder  $callback
     */
    public function applyLanguageUsing(\Closure $callback): self
    {
        return $this->cloneWith(applyLanguageUsing: $callback);
    }

    /**
     * Reuse list capabilities from a Filament resource (query, search, language, table filters).
     *
     * @param  class-string  $resourceClass
     * @param  bool  $useFilamentTableToolbar  Use Filament table toolbar for search, filters, and language switcher (1:1 with list).
     */
    public function forwardFromResource(string $resourceClass, bool $useFilamentTableToolbar = false): self
    {
        $configuration = $this
            ->cloneWith(
                sourceResourceClass: $resourceClass,
                useFilamentTableToolbar: $useFilamentTableToolbar,
                filamentTableLanguageSwitcherEnabled: $useFilamentTableToolbar,
            )
            ->modifyQuery(fn (Builder $query): Builder => ResourceListForwarder::baseQuery($resourceClass))
            ->applyLanguageUsing(
                fn (Builder $query, string $lang, self $config): Builder => ResourceListForwarder::applyLanguage(
                    $resourceClass,
                    $query,
                    $lang,
                ),
            );

        if ($useFilamentTableToolbar) {
            return self::bindForwardedSearch($configuration);
        }

        $configuration = $configuration->toolbarLanguageSwitcher();

        return self::bindForwardedSearch($configuration->toolbarSearch());
    }

    private static function bindForwardedSearch(self $configuration): self
    {
        $resourceClass = $configuration->getSourceResourceClass();

        return $configuration->applySearchUsing(
            fn (Builder $query, string $search, self $config): Builder => ResourceListForwarder::applySearch(
                $resourceClass,
                $query,
                $search,
                TreeLocale::resolveActiveLanguage(),
            ),
        );
    }

    /**
     * @return class-string|null
     */
    public function getSourceResourceClass(): ?string
    {
        return $this->sourceResourceClass;
    }

    /**
     * Toolbar search + language switcher scoped to a translations relation (Moox Localization aware).
     */
    public function toolbarLocalizedTranslations(
        string $translationsRelation = 'translations',
        string $localeColumn = 'locale',
        ?string $translationSearchColumn = null,
    ): self {
        $searchColumn = $translationSearchColumn ?? $this->getLabelColumn();

        return $this
            ->toolbarSearch()
            ->toolbarLanguageSwitcher()
            ->applySearchUsing(function (Builder $query, string $search) use ($translationsRelation, $localeColumn, $searchColumn): Builder {
                $localeCandidates = TreeLocale::localeCandidates(TreeLocale::resolveActiveLanguage());

                return $query->whereHas($translationsRelation, function (Builder $translationQuery) use ($search, $localeCandidates, $localeColumn, $searchColumn): Builder {
                    return $translationQuery
                        ->when(
                            $localeCandidates !== [],
                            fn (Builder $localizedQuery): Builder => $localizedQuery->whereIn($localeColumn, $localeCandidates),
                        )
                        ->where($searchColumn, 'like', '%'.$search.'%');
                });
            })
            ->applyLanguageUsing(function (Builder $query, string $lang) use ($translationsRelation, $localeColumn): Builder {
                TreeLocale::syncApplicationLocale($lang);

                $localeCandidates = TreeLocale::localeCandidates($lang);

                if ($localeCandidates === []) {
                    return $query;
                }

                return $query->whereHas($translationsRelation, function (Builder $translationQuery) use ($localeCandidates, $localeColumn): Builder {
                    return $translationQuery->whereIn($localeColumn, $localeCandidates);
                });
            });
    }

    public function labels(
        ?string $treeHeading = null,
        ?string $treeSubheading = null,
        ?string $inspectorHeading = null,
        ?string $createRootLabel = null,
        ?string $saveLabel = null,
        ?string $newRecordLabel = null,
        ?string $deleteConfirmMessage = null,
    ): self {
        return $this->cloneWith(
            treeHeading: $treeHeading ?? $this->treeHeading,
            treeSubheading: $treeSubheading ?? $this->treeSubheading,
            inspectorHeading: $inspectorHeading ?? $this->inspectorHeading,
            createRootLabel: $createRootLabel ?? $this->createRootLabel,
            saveLabel: $saveLabel ?? $this->saveLabel,
            newRecordLabel: $newRecordLabel ?? $this->newRecordLabel,
            deleteConfirmMessage: $deleteConfirmMessage ?? $this->deleteConfirmMessage,
        );
    }

    public function modelClass(): string
    {
        return $this->modelClass;
    }

    public function getParentColumn(): string
    {
        return $this->parentColumn;
    }

    public function getSortColumn(): string
    {
        return $this->sortColumn;
    }

    public function getLabelColumn(): string
    {
        return $this->labelColumn;
    }

    public function isLabelColumnQueryable(): bool
    {
        return $this->labelColumnQueryable;
    }

    public function getLabelFallbackColumn(): ?string
    {
        return $this->labelFallbackColumn;
    }

    public function usesNestedSet(): bool
    {
        return $this->nestedSet;
    }

    public function isReorderable(): bool
    {
        return $this->reorderable;
    }

    public function applyQuery(Builder $query): Builder
    {
        return $this->queries()->applyQuery($query);
    }

    public function applyQueryClosure(Builder $query): Builder
    {
        if ($this->modifyQuery === null) {
            return $query;
        }

        return ($this->modifyQuery)($query);
    }

    public function newQuery(): Builder
    {
        return $this->queries()->newQuery();
    }

    public function siblingsQuery(int|string|null $parentId): Builder
    {
        return $this->queries()->siblingsQuery($parentId);
    }

    public function siblingsExcept(int|string|null $parentId, int|string|null $excludeId): Builder
    {
        return $this->queries()->siblingsExcept($parentId, $excludeId);
    }

    public function nextSortOrder(int|string|null $parentId): int
    {
        return $this->queries()->nextSortOrder($parentId);
    }

    /**
     * @return (\Closure(Builder, string, self): Builder)|null
     */
    public function getApplySearchUsing(): ?\Closure
    {
        return $this->applySearchUsing;
    }

    /**
     * @return (\Closure(Builder, string, self): Builder)|null
     */
    public function getApplyLanguageUsing(): ?\Closure
    {
        return $this->applyLanguageUsing;
    }

    public function queries(): TreeIndexQueryBuilder
    {
        return new TreeIndexQueryBuilder($this);
    }

    public function getAuthorizationAbility(): ?string
    {
        return $this->authorizationAbility;
    }

    public function treeHeading(): string
    {
        return $this->treeHeading;
    }

    public function treeSubheading(): string
    {
        return $this->treeSubheading;
    }

    public function inspectorHeading(): string
    {
        return $this->inspectorHeading;
    }

    public function createRootLabel(): string
    {
        return $this->createRootLabel;
    }

    public function saveLabel(): string
    {
        return $this->saveLabel;
    }

    public function newRecordLabel(): string
    {
        return $this->newRecordLabel;
    }

    public function deleteConfirmMessage(): string
    {
        return $this->deleteConfirmMessage;
    }

    /**
     * @return array<int, string>
     */
    public function treeSelectColumns(): array
    {
        $columns = ['id', $this->getParentColumn(), $this->getSortColumn()];

        if ($this->usesNestedSet()) {
            $columns[] = '_rgt';
        }

        if ($this->isLabelColumnQueryable()) {
            $columns[] = $this->getLabelColumn();
        }

        return array_values(array_unique($columns));
    }

    public function applyTreeOrdering(Builder $query): Builder
    {
        return $this->queries()->applyTreeOrdering($query);
    }

    public function isToolbarSearchEnabled(): bool
    {
        return $this->toolbarSearchEnabled;
    }

    public function isToolbarLanguageSwitcherEnabled(): bool
    {
        return $this->toolbarLanguageSwitcherEnabled;
    }

    public function isFilamentTableLanguageSwitcherEnabled(): bool
    {
        return $this->filamentTableLanguageSwitcherEnabled;
    }

    public function usesFilamentTableToolbar(): bool
    {
        return $this->useFilamentTableToolbar;
    }

    public function applySearch(Builder $query, string $search): Builder
    {
        return $this->queries()->applySearch($query, $search);
    }

    public function applyLanguage(Builder $query, string $lang): Builder
    {
        return $this->queries()->applyLanguage($query, $lang);
    }

    private function cloneWith(
        ?string $modelClass = null,
        ?string $sourceResourceClass = null,
        ?string $parentColumn = null,
        ?string $sortColumn = null,
        ?string $labelColumn = null,
        ?bool $labelColumnQueryable = null,
        ?string $labelFallbackColumn = null,
        ?bool $nestedSet = null,
        ?bool $reorderable = null,
        ?string $inspectorPageClass = null,
        ?string $inspectorCreatePageClass = null,
        ?bool $stubCreate = null,
        ?string $authorizationAbility = null,
        ?string $treeHeading = null,
        ?string $treeSubheading = null,
        ?string $inspectorHeading = null,
        ?string $createRootLabel = null,
        ?string $saveLabel = null,
        ?string $newRecordLabel = null,
        ?string $deleteConfirmMessage = null,
        ?bool $toolbarSearchEnabled = null,
        ?bool $toolbarLanguageSwitcherEnabled = null,
        ?bool $filamentTableLanguageSwitcherEnabled = null,
        ?bool $useFilamentTableToolbar = null,
        ?\Closure $modifyQuery = null,
        ?\Closure $applySearchUsing = null,
        ?\Closure $applyLanguageUsing = null,
    ): self {
        return new self(
            modelClass: $modelClass ?? $this->modelClass,
            sourceResourceClass: $sourceResourceClass ?? $this->sourceResourceClass,
            parentColumn: $parentColumn ?? $this->parentColumn,
            sortColumn: $sortColumn ?? $this->sortColumn,
            labelColumn: $labelColumn ?? $this->labelColumn,
            labelColumnQueryable: $labelColumnQueryable ?? $this->labelColumnQueryable,
            labelFallbackColumn: $labelFallbackColumn ?? $this->labelFallbackColumn,
            nestedSet: $nestedSet ?? $this->nestedSet,
            reorderable: $reorderable ?? $this->reorderable,
            inspectorPageClass: $inspectorPageClass ?? $this->inspectorPageClass,
            inspectorCreatePageClass: $inspectorCreatePageClass ?? $this->inspectorCreatePageClass,
            stubCreate: $stubCreate ?? $this->stubCreate,
            authorizationAbility: $authorizationAbility ?? $this->authorizationAbility,
            treeHeading: $treeHeading ?? $this->treeHeading,
            treeSubheading: $treeSubheading ?? $this->treeSubheading,
            inspectorHeading: $inspectorHeading ?? $this->inspectorHeading,
            createRootLabel: $createRootLabel ?? $this->createRootLabel,
            saveLabel: $saveLabel ?? $this->saveLabel,
            newRecordLabel: $newRecordLabel ?? $this->newRecordLabel,
            deleteConfirmMessage: $deleteConfirmMessage ?? $this->deleteConfirmMessage,
            toolbarSearchEnabled: $toolbarSearchEnabled ?? $this->toolbarSearchEnabled,
            toolbarLanguageSwitcherEnabled: $toolbarLanguageSwitcherEnabled ?? $this->toolbarLanguageSwitcherEnabled,
            filamentTableLanguageSwitcherEnabled: $filamentTableLanguageSwitcherEnabled ?? $this->filamentTableLanguageSwitcherEnabled,
            useFilamentTableToolbar: $useFilamentTableToolbar ?? $this->useFilamentTableToolbar,
            modifyQuery: $modifyQuery ?? $this->modifyQuery,
            applySearchUsing: $applySearchUsing ?? $this->applySearchUsing,
            applyLanguageUsing: $applyLanguageUsing ?? $this->applyLanguageUsing,
        );
    }
}
