<?php

namespace Moox\Press\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class WpUserResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->resource->id,
            'user_login' => $this->resource->name,
            'user_nickname' => $this->resource->nickname,
            'user_email' => $this->resource->email,
            'first_name' => $this->resource->first_name,
            'last_name' => $this->resource->last_name,
            'description' => $this->resource->description,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];

        foreach ($this->resource->getAllMetaAttributes() as $key => $value) {
            $data[$key] = $value;
        }

        return $data;
    }
}
