<?php

namespace Moox\Sync\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property string $name
 * @property string $domain
 * @property string $ip_address
 * @property bool $show_in_menu
 * @property int $order
 * @property bool $read_only
 * @property bool $locked
 * @property string $lock_reason
 * @property bool $master
 * @property string $thumbnail
 * @property string $api_token
 */
class PlatformResource extends JsonResource
{
    public function toArray($request): array
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
        ];
    }
}
