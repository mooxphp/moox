<?php

namespace Moox\Sync\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SyncResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'title' => $this->title,
            'source_platform_id' => $this->source_platform_id,
            'target_platform_id' => $this->target_platform_id,
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
