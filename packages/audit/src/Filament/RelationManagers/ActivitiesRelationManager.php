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
use Moox\Audit\Support\ActivityEntryPresenter;
use Moox\Audit\Support\AuditFilamentRegistry;
use Override;

class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'auditActivities';

    #[Override]
    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('core::audit.activity');
    }

    #[Override]
    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['causer', 'subject']))
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('core::audit.occurred_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('entry_type')
                    ->label(__('core::audit.entry_type'))
                    ->badge()
                    ->toggleable(),
                TextColumn::make('log_name')
                    ->label(__('core::audit.log_name'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('event')
                    ->label(__('core::audit.action'))
                    ->state(fn (Activity $record): string => ActivityEntryPresenter::eventLabel($record))
                    ->badge()
                    ->toggleable(),
                TextColumn::make('subject_label')
                    ->label(__('core::audit.subject'))
                    ->state(fn (Activity $record): string => ActivityEntryPresenter::subjectLabel($record))
                    ->limit(40),
                TextColumn::make('causer_label')
                    ->label(__('core::audit.causer'))
                    ->state(fn (Activity $record): string => ActivityEntryPresenter::causerLabel($record))
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (Activity $record): string => AuditResource::getUrl('view', ['record' => $record]))
            ->paginated([10, 25, 50]);
    }

    #[Override]
    protected function getTableQuery(): ?Builder
    {
        $owner = $this->getOwnerRecord();
        $config = AuditFilamentRegistry::configForOwner($owner);

        if ($config !== null && is_array($config['aggregate_subjects'] ?? null)) {
            return $this->aggregatedActivitiesQuery($owner, $config['aggregate_subjects'])
                ->with(['causer', 'subject']);
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
