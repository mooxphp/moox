<?php

namespace Moox\Core\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Moox\Core\Models\Scope;
use Moox\Core\Support\Scopes\ScopeValue;

class ScopeAssignmentValidator
{
    /**
     * @return array{allowed: bool, reason?: string}
     */
    public function validate(Model $record, string $targetScope, ?Authenticatable $actor = null): array
    {
        if (blank($targetScope)) {
            // "Global" means unassigned: always allowed, independent of DB scope rows.
            return ['allowed' => true];
        }

        try {
            $parsedScope = ScopeValue::parse($targetScope);
        } catch (InvalidArgumentException) {
            return [
                'allowed' => false,
                'reason' => 'Target scope is empty or invalid.',
            ];
        }

        if ($parsedScope === null) {
            return [
                'allowed' => false,
                'reason' => 'Target scope is empty or invalid.',
            ];
        }

        // Prevent scope assignment across unrelated "origins".
        // This closes the gap where a malicious user could tamper with the request payload
        // and submit an active scope from a different origin than the record supports.
        if (method_exists($record, 'resolveScopeOrigin')) {
            /** @var string|null $expectedOrigin */
            $expectedOrigin = $record->resolveScopeOrigin();

            if (blank($expectedOrigin) || $expectedOrigin !== $parsedScope->origin()) {
                return [
                    'allowed' => false,
                    'reason' => 'Target scope origin does not match the record.',
                ];
            }
        }

        if (! $this->isTargetScopeActive((string) $parsedScope)) {
            return [
                'allowed' => false,
                'reason' => 'Target scope is not active.',
            ];
        }

        if (! $this->boundaryRuleSatisfied($parsedScope, $actor)) {
            return [
                'allowed' => false,
                'reason' => 'Boundary rules are not fulfilled for target scope.',
            ];
        }

        return ['allowed' => true];
    }

    protected function isTargetScopeActive(string $targetScope): bool
    {
        if (! Schema::hasTable('scopes')) {
            return true;
        }

        $active = Scope::query()
            ->where('scope', $targetScope)
            ->value('is_active');

        return (bool) $active;
    }

    protected function boundaryRuleSatisfied(ScopeValue $scope, ?Authenticatable $actor): bool
    {
        return match ($scope->boundary()) {
            ScopeValue::MODE_PRIVATE,
            ScopeValue::MODE_PUBLIC => true,
            ScopeValue::MODE_USER => $this->matchesUserBoundary($scope, $actor),
            ScopeValue::MODE_USER_TYPE => $this->matchesUserTypeBoundary($scope, $actor),
            ScopeValue::MODE_GROUP => $this->matchesGroupBoundary($scope, $actor),
            default => false,
        };
    }

    protected function matchesUserBoundary(ScopeValue $scope, ?Authenticatable $actor): bool
    {
        if (! $actor) {
            return false;
        }

        $context = strtolower($scope->context());
        $actorId = strtolower((string) $actor->getAuthIdentifier());

        // Convention: user_123
        if ($context === 'user_'.$actorId) {
            return true;
        }

        // Backward-compatible variants
        return in_array($context, [$actorId, 'user:'.$actorId], true);
    }

    protected function matchesUserTypeBoundary(ScopeValue $scope, ?Authenticatable $actor): bool
    {
        if (! $actor) {
            return false;
        }

        $context = strtolower($scope->context());
        $expected = $this->resolveActorModelTypeContext($actor);

        if ($expected !== null && $context === $expected) {
            return true;
        }

        // Backward-compatible: allow older "types" matching (roles/custom type field)
        return in_array($context, $this->resolveActorTypes($actor), true);
    }

    protected function matchesGroupBoundary(ScopeValue $scope, ?Authenticatable $actor): bool
    {
        if (! $actor) {
            return false;
        }

        $context = strtolower($scope->context());
        // Convention: group_<id> or group_<slug>
        $groups = $this->resolveActorGroups($actor);

        return in_array($context, $groups, true);
    }

    protected function resolveActorModelTypeContext(Authenticatable $actor): ?string
    {
        $classBase = class_basename($actor::class);

        if ($classBase === '') {
            return null;
        }

        // Convention: model_app_user / model_moox_user
        return 'model_'.strtolower(str_replace('\\', '_', $classBase));
    }

    /**
     * @return list<string>
     */
    protected function resolveActorTypes(Authenticatable $actor): array
    {
        $types = [strtolower(class_basename($actor::class))];

        if (method_exists($actor, 'getRoleNames')) {
            foreach ((array) $actor->getRoleNames()->toArray() as $roleName) {
                if (is_string($roleName) && $roleName !== '') {
                    $types[] = strtolower($roleName);
                }
            }
        }

        $actorType = data_get($actor, 'type');
        if (is_string($actorType) && $actorType !== '') {
            $types[] = strtolower($actorType);
        }

        return array_values(array_unique($types));
    }

    /**
     * @return list<string>
     */
    protected function resolveActorGroups(Authenticatable $actor): array
    {
        $groups = [];

        $groupId = data_get($actor, 'group_id');
        if ($groupId !== null && $groupId !== '') {
            $id = strtolower((string) $groupId);
            $groups[] = 'group_'.$id;
            $groups[] = $id; // backward
            $groups[] = 'group:'.$id; // backward
        }

        if (method_exists($actor, 'group')) {
            $group = data_get($actor, 'group');
            if ($group instanceof Model) {
                $key = strtolower((string) $group->getKey());
                $groups[] = 'group_'.$key;
                $groups[] = $key; // backward
                if (isset($group->name) && is_string($group->name) && $group->name !== '') {
                    $name = strtolower($group->name);
                    $groups[] = 'group_'.$name;
                    $groups[] = $name; // backward
                }
            }
        }

        if (method_exists($actor, 'groups')) {
            $actorGroups = data_get($actor, 'groups');
            if (is_iterable($actorGroups)) {
                foreach ($actorGroups as $group) {
                    if ($group instanceof Model) {
                        $key = strtolower((string) $group->getKey());
                        $groups[] = 'group_'.$key;
                        $groups[] = $key; // backward
                        if (isset($group->name) && is_string($group->name) && $group->name !== '') {
                            $name = strtolower($group->name);
                            $groups[] = 'group_'.$name;
                            $groups[] = $name; // backward
                        }
                    }
                }
            }
        }

        return array_values(array_unique($groups));
    }
}
