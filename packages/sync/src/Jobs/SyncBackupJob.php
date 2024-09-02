<?php

namespace Moox\Sync\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Moox\Sync\Models\Sync;

class SyncBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $syncs = Sync::where('status', true)->get();

        foreach ($syncs as $sync) {
            $this->processSyncConfig($sync);
        }
    }

    protected function processSyncConfig(Sync $sync)
    {
        $sourceModel = $sync->source_model;
        $targetModel = $sync->target_model;

        $sourceData = $sourceModel::all();

        foreach ($sourceData as $sourceItem) {
            $targetItem = $targetModel::find($sourceItem->id);

            if (! $targetItem) {
                $this->createTargetItem($targetModel, $sourceItem, $sync);
            } else {
                $this->updateTargetItem($targetItem, $sourceItem, $sync);
            }
        }

        $this->handleDeletedItems($sync, $sourceData);
    }

    protected function createTargetItem($targetModel, $sourceItem, Sync $sync)
    {
        $data = $this->mapFields($sourceItem, $sync);
        $targetModel::create($data);
    }

    protected function updateTargetItem($targetItem, $sourceItem, Sync $sync)
    {
        $data = $this->mapFields($sourceItem, $sync);
        $targetItem->update($data);
    }

    protected function mapFields($sourceItem, Sync $sync)
    {
        if ($sync->sync_all_fields) {
            return $sourceItem->toArray();
        }

        $mappedData = [];
        foreach ($sync->field_mappings as $sourceField => $targetField) {
            $mappedData[$targetField] = $sourceItem->$sourceField;
        }

        return $mappedData;
    }

    protected function handleDeletedItems(Sync $sync, $sourceData)
    {
        $targetModel = $sync->target_model;
        $sourceIds = $sourceData->pluck('id');

        $targetModel::whereNotIn('id', $sourceIds)->delete();
    }
}
