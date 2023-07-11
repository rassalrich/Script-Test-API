<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SceneResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'scene_id' => $this->scene_id,
            'master_id' => $this->master_id,
            'image' => $this->image,
        ];
    }
}
