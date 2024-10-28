<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Generators;

interface Feature
{
    /**
     * Get form fields for this feature
     *
     * @return array<string>
     */
    public function getFormFields(): array;

    /**
     * Get table columns for this feature
     *
     * @return array<string>
     */
    public function getTableColumns(): array;

    /**
     * Get table filters for this feature
     *
     * @return array<string>
     */
    public function getTableFilters(): array;

    /**
     * Get actions for this feature
     *
     * @return array<string>
     */
    public function getActions(): array;

    /**
     * Get migration statements for this feature
     *
     * @return array<string>
     */
    public function getMigrations(): array;

    /**
     * Get use statements for a specific context and subcontext
     *
     * @return array<string>
     */
    public function getUseStatements(string $context, ?string $subContext = null): array;

    /**
     * Get traits for a specific context
     *
     * @return array<string>
     */
    public function getTraits(string $context, ?string $subContext = null): array;

    /**
     * Get methods for a specific context
     *
     * @return array<string>
     */
    public function getMethods(string $context, ?string $subContext = null): array;

    /**
     * Get model use statements for this feature
     *
     * @return array<string>
     */
    public function getModelUseStatements(): array;

    /**
     * Get model traits for this feature
     *
     * @return array<string>
     */
    public function getModelTraits(): array;

    /**
     * Get model methods for this feature
     *
     * @return array<string>
     */
    public function getModelMethods(): array;

    /**
     * Get plugin use statements for this feature
     *
     * @return array<string>
     */
    public function getPluginUseStatements(): array;

    /**
     * Get plugin boot methods for this feature
     *
     * @return array<string>
     */
    public function getPluginBootMethods(): array;

    /**
     * Get plugin methods for this feature
     *
     * @return array<string>
     */
    public function getPluginMethods(): array;
}
