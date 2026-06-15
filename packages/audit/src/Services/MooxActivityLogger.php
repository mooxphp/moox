<?php

declare(strict_types=1);

namespace Moox\Audit\Services;

use Illuminate\Database\Eloquent\Model;
use Moox\Audit\Support\CauserResolver;
use Moox\Audit\Support\ScopeResolver;
use Moox\Audit\Support\UserAttributePresenter;
use Spatie\Activitylog\Contracts\Activity as ActivityContract;

final class MooxActivityLogger
{
    /**
     * @param  array<string, mixed>  $options
     */
    public static function log(string $logName, string $description, array $options = []): ?ActivityContract
    {
        if (! function_exists('activity')) {
            return null;
        }

        $builder = activity($logName);

        if (isset($options['event']) && is_string($options['event'])) {
            $builder->event($options['event']);
        }

        if (isset($options['subject']) && $options['subject'] instanceof Model) {
            $builder->performedOn($options['subject']);
        }

        if (isset($options['properties']) && is_array($options['properties'])) {
            $builder->withProperties($options['properties']);
        }

        $causer = $options['causer'] ?? CauserResolver::resolve();

        if ($causer instanceof Model) {
            $builder->causedBy($causer);
        }

        return $builder
            ->tap(function (ActivityContract $activity) use ($options): void {
                $activity->entry_type = $options['entry_type'] ?? 'log';

                if (array_key_exists('scope', $options)) {
                    $activity->scope = $options['scope'];
                }
            })
            ->log($description);
    }

    /**
     * @param  array<string, mixed>  $changes
     * @param  array<string, mixed>  $config
     */
    public static function audit(
        Model $subject,
        string $event,
        array $changes,
        array $config,
        string $logName,
    ): ?ActivityContract {
        if (! function_exists('activity')) {
            return null;
        }

        $properties = self::buildProperties($subject, $config, $changes);

        $builder = activity($logName)
            ->event($event)
            ->performedOn($subject)
            ->withChanges($changes)
            ->withProperties($properties)
            ->tap(function (ActivityContract $activity) use ($config, $subject): void {
                $activity->entry_type = $config['entry_type'] ?? config('audit.default_entry_type', 'audit');
                $activity->scope = ScopeResolver::forModel($subject);
            });

        $causer = CauserResolver::resolve();

        if ($causer instanceof Model) {
            $builder->causedBy($causer);
        }

        return $builder->log($event);
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    private static function buildProperties(Model $subject, array $config, array $changes): array
    {
        $properties = [];

        foreach ($config['properties'] ?? [] as $property) {
            if (! is_string($property)) {
                continue;
            }

            $value = $subject->getAttribute($property);

            if ($value !== null) {
                $properties[$property] = $value;
            }
        }

        $userModels = $config['user_models'] ?? [];

        if (is_array($userModels) && $userModels !== []) {
            $properties = array_merge($properties, UserAttributePresenter::enrichProperties($changes, $userModels));
        }

        return $properties;
    }
}
