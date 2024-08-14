<?php

namespace Moox\Sync\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PlatformResource extends JsonResource
{
    public function toArray(Request|\Illuminate\Http\Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'domain' => $this->domain,
            'ip_address' => $this->ip_address,
            'show_in_menu' => $this->show_in_menu,
            'order' => $this->order,
            'read_only' => $this->read_only,
            'locked' => $this->locked,
            'lock_reason' => $this->lock_reason,
            'master' => $this->master,
            'thumbnail' => $this->thumbnail,
            'api_token' => $this->api_token,
            'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toDateTimeString() : null,
            'sources' => SyncResource::collection($this->whenLoaded('sources')),
            'targets' => SyncResource::collection($this->whenLoaded('targets')),
        ];
    }
}
