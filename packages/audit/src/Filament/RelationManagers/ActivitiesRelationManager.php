<?php

declare(strict_types=1);

namespace Moox\Audit\Filament\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Moox\Audit\Models\Activity;
use Moox\Audit\Resources\AuditResource;
use Moox\Audit\Support\AuditFilamentRegistry;
use Override;

class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'auditActivities';

    protected static ?string $title = 'Activity';

    #[Override]
    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('core::common.created_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('entry_type')
                    ->label(__('core::audit.entry_type'))
                    ->badge(),
                TextColumn::make('event')
                    ->label(__('core::common.event'))
                    ->toggleable(),
                TextColumn::make('description')
                    ->label(__('core::common.description'))
                    ->limit(60),
                TextColumn::make('causer.name')
                    ->label(__('core::audit.causer_id'))
                    ->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (Activity $record): string => AuditResource::getUrl('view', ['record' => $record]))
            ->paginated([10, 25, 50]);
    }

    #[Override]
    protected function getTableQuery(): Builder
    {
        $owner = $this->getOwnerRecord();
        $config = AuditFilamentRegistry::configForOwner($owner);

        if ($config !== null && is_array($config['aggregate_subjects'] ?? null)) {
            return $this->aggregatedActivitiesQuery($owner, $config['aggregate_subjects']);
        }

        return parent::getTableQuery();
    }

    /**
     * @param  array<class-string<Model>, string>  $aggregateSubjects
     */
    private function aggregatedActivitiesQuery(Model $owner, array $aggregateSubjects): Builder
    {
        /** @var class-string<Model> $activityModel */
        $activityModel = config('audit.activity_model', Activity::class);

        $subjectGroups = [
            [$owner::class, [$owner->getKey()]],
        ];

        foreach ($aggregateSubjects as $relatedModelClass => $relationName) {
            if (! is_string($relatedModelClass) || ! is_string($relationName) || ! method_exists($owner, $relationName)) {
                continue;
            }

            /** @var Relation<Model, Model, mixed> $relation */
            $relation = $owner->{$relationName}();
            $relatedIds = $relation->pluck($relation->getRelated()->getQualifiedKeyName());

            if ($relatedIds->isNotEmpty()) {
                $subjectGroups[] = [$relatedModelClass, $relatedIds->all()];
            }
        }

        return $activityModel::query()
            ->where(function (Builder $query) use ($subjectGroups): void {
                foreach ($subjectGroups as [$subjectType, $subjectIds]) {
                    $ids = is_array($subjectIds) ? $subjectIds : [$subjectIds];

                    $query->orWhere(function (Builder $inner) use ($subjectType, $ids): void {
                        $inner->where('subject_type', $subjectType)
                            ->whereIn('subject_id', $ids);
                    });
                }
            })
            ->latest('created_at');
    }
}
