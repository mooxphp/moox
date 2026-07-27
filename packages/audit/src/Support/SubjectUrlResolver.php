<?php

declare(strict_types=1);

namespace Moox\Audit\Support;

use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Moox\Audit\Models\Activity;
use Throwable;

final class SubjectUrlResolver
{
    public static function forActivity(Activity $activity): ?string
    {
        $subject = $activity->subject;

        if (! $subject instanceof Model) {
            return null;
        }

        return self::forModel($subject);
    }

    public static function forModel(Model $subject): ?string
    {
        $fromRegistry = self::fromAuditFilamentRegistry($subject);

        if ($fromRegistry !== null) {
            return $fromRegistry;
        }

        return self::fromFilamentPanel($subject);
    }

    private static function fromAuditFilamentRegistry(Model $subject): ?string
    {
        foreach (AuditFilamentRegistry::all() as $resourceClass => $config) {
            if (! is_string($resourceClass) || ! class_exists($resourceClass) || ! is_array($config)) {
                continue;
            }

            $ownerModel = $config['owner_model'] ?? null;

            if (! is_string($ownerModel) || ! class_exists($ownerModel)) {
                continue;
            }

            if ($subject instanceof $ownerModel) {
                return self::resourceUrl($resourceClass, $subject);
            }

            $aggregateSubjects = $config['aggregate_subjects'] ?? [];

            if (! is_array($aggregateSubjects) || ! array_key_exists($subject::class, $aggregateSubjects)) {
                continue;
            }

            $owner = self::resolveOwnerRecord($subject, $ownerModel);

            if ($owner instanceof Model) {
                return self::resourceUrl($resourceClass, $owner, self::localeFromSubject($subject));
            }
        }

        return null;
    }

    private static function fromFilamentPanel(Model $subject): ?string
    {
        try {
            $panel = Filament::getCurrentPanel() ?? Filament::getDefaultPanel();
        } catch (Throwable) {
            return null;
        }

        if ($panel === null) {
            return null;
        }

        try {
            $resourceClass = $panel->getModelResource($subject);
        } catch (Throwable) {
            return null;
        }

        if (is_string($resourceClass) && class_exists($resourceClass)) {
            return self::resourceUrl($resourceClass, $subject);
        }

        if (! str_ends_with($subject::class, 'Translation')) {
            return null;
        }

        $ownerClass = Str::replaceEnd('Translation', '', $subject::class);

        if (! class_exists($ownerClass) || ! is_subclass_of($ownerClass, Model::class)) {
            return null;
        }

        $owner = self::resolveOwnerRecord($subject, $ownerClass);

        if (! $owner instanceof Model) {
            return null;
        }

        try {
            $ownerResource = $panel->getModelResource($owner);
        } catch (Throwable) {
            return null;
        }

        if (! is_string($ownerResource) || ! class_exists($ownerResource)) {
            return null;
        }

        return self::resourceUrl($ownerResource, $owner, self::localeFromSubject($subject));
    }

    /**
     * @param  class-string<Resource>  $resourceClass
     */
    private static function resourceUrl(string $resourceClass, Model $record, ?string $locale = null): ?string
    {
        $parameters = ['record' => $record];

        if (is_string($locale) && $locale !== '') {
            $parameters['lang'] = $locale;
        }

        foreach (['edit', 'view'] as $page) {
            if (! $resourceClass::hasPage($page)) {
                continue;
            }

            try {
                return $resourceClass::getUrl($page, $parameters, shouldGuessMissingParameters: false);
            } catch (Throwable) {
                try {
                    return $resourceClass::getUrl($page, ['record' => $record], shouldGuessMissingParameters: false);
                } catch (Throwable) {
                    continue;
                }
            }
        }

        return null;
    }

    /**
     * @param  class-string<Model>  $ownerClass
     */
    private static function resolveOwnerRecord(Model $subject, string $ownerClass): ?Model
    {
        $foreignKey = Str::snake(class_basename($ownerClass)).'_id';
        $ownerId = $subject->getAttribute($foreignKey);

        if ($ownerId === null || $ownerId === '') {
            $tableForeignKey = Str::replaceEnd('_translations', '_id', $subject->getTable());

            if ($tableForeignKey !== $subject->getTable()) {
                $ownerId = $subject->getAttribute($tableForeignKey);
                $foreignKey = $tableForeignKey;
            }
        }

        if ($ownerId === null || $ownerId === '') {
            return null;
        }

        return $ownerClass::query()->find($ownerId);
    }

    private static function localeFromSubject(Model $subject): ?string
    {
        $locale = $subject->getAttribute('locale');

        return is_string($locale) && $locale !== '' ? $locale : null;
    }
}
