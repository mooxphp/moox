<?php

declare(strict_types=1);

namespace Moox\Audit\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Moox\Audit\Filament\RelationManagers\ActivitiesRelationManager;
use Moox\Audit\Models\Activity;
use Moox\Audit\Observers\ConfigDrivenModelObserver;

final class AuditBootstrap
{
    /** @var array<class-string<Model>, true> */
    private static array $registeredModels = [];

    /** @var array<class-string, true> */
    private static array $registeredFilamentResources = [];

    private static bool $hooksRegistered = false;

    public static function boot(): void
    {
        if (! config('audit.enabled', true)) {
            return;
        }

        self::registerConfiguredModels();
        self::registerHooks();
        self::registerFilamentRelations();
    }

    public static function clear(): void
    {
        self::$registeredModels = [];
        self::$registeredFilamentResources = [];
        self::$hooksRegistered = false;
        AuditPackageRegistry::clear();
        AuditFilamentRegistry::clear();
        AuditResourceRelationRegistry::clear();
    }

    private static function registerConfiguredModels(): void
    {
        $observer = app(ConfigDrivenModelObserver::class);

        foreach (AuditConfigResolver::allTrackedModelClasses() as $modelClass) {
            if (! is_string($modelClass) || isset(self::$registeredModels[$modelClass])) {
                continue;
            }

            if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
                continue;
            }

            if (AuditConfigResolver::resolveModel($modelClass) === null) {
                continue;
            }

            self::registerAuditActivitiesRelation($modelClass);
            self::registerModelEventHooks($modelClass, $observer);
            self::$registeredModels[$modelClass] = true;
        }
    }

    private static function registerHooks(): void
    {
        if (self::$hooksRegistered) {
            return;
        }

        foreach (AuditConfigResolver::resolvedHooks() as $modelClass => $hooks) {
            if (! is_string($modelClass) || ! class_exists($modelClass) || ! is_array($hooks)) {
                continue;
            }

            foreach ($hooks as $event => $hookConfig) {
                if (! is_string($event) || ! is_array($hookConfig)) {
                    continue;
                }

                AuditHooks::registerHook($modelClass, $event, $hookConfig);
            }
        }

        self::$hooksRegistered = true;
    }

    private static function registerFilamentRelations(): void
    {
        foreach (AuditConfigResolver::resolvedFilament() as $resourceClass => $resourceConfig) {
            if (! is_string($resourceClass) || ! class_exists($resourceClass) || ! is_array($resourceConfig)) {
                continue;
            }

            if (isset(self::$registeredFilamentResources[$resourceClass])) {
                continue;
            }

            AuditFilamentRegistry::register($resourceClass, $resourceConfig);

            AuditResourceRelationRegistry::register($resourceClass, [
                ActivitiesRelationManager::class,
            ]);

            self::$registeredFilamentResources[$resourceClass] = true;
        }
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private static function registerModelEventHooks(string $modelClass, ConfigDrivenModelObserver $observer): void
    {
        $events = ['created', 'updating', 'updated', 'deleted', 'restored'];

        foreach ($events as $event) {
            if (! method_exists($observer, $event)) {
                continue;
            }

            $modelClass::$event(function (Model $model) use ($observer, $event): void {
                $observer->{$event}($model);
            });
        }
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private static function registerAuditActivitiesRelation(string $modelClass): void
    {
        $modelClass::resolveRelationUsing('auditActivities', function (Model $model): MorphMany {
            /** @var class-string<Model> $activityModel */
            $activityModel = config('audit.activity_model', Activity::class);

            return $model->morphMany($activityModel, 'subject');
        });
    }
}
