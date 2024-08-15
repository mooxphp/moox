<?php

namespace Moox\Sync\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property bool $status
 * @property string $title
 * @property PlatformResource $source_platform
 * @property string $source_model
 * @property PlatformResource $target_platform
 * @property string $target_model
 * @property bool $use_platform_relations
 * @property string $if_exists
 * @property array $sync_ids
 * @property bool $sync_all_fields
 * @property array $field_mappings
 * @property string $use_transformer_class
 * @property bool $has_errors
 * @property string $error_message
 * @property int $interval
 * @property \Illuminate\Support\Carbon $last_sync
 */

class SyncResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'title' => $this->title,
            'source_platform' => new PlatformResource($this->whenLoaded('sourcePlatform')),
            'source_model' => $this->source_model,
            'target_platform' => new PlatformResource($this->whenLoaded('targetPlatform')),
            'target_model' => $this->target_model,
            'use_platform_relations' => $this->use_platform_relations,
            'if_exists' => $this->if_exists,
            'sync_ids' => $this->sync_ids,
            'sync_all_fields' => $this->sync_all_fields,
            'field_mappings' => $this->field_mappings,
            'use_transformer_class' => $this->use_transformer_class,
            'has_errors' => $this->has_errors,
            'error_message' => $this->error_message,
            'interval' => $this->interval,
            'last_sync' => $this->last_sync,
        ];
    }
}
