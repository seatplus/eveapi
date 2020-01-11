<?php


namespace Seatplus\Eveapi\Http\Resources;


use Illuminate\Http\Resources\Json\JsonResource;

class CharacterAsset extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {

        return [
            'quantity' => $this->quantity,
            'type' => $this->whenLoaded('type'),
            'name' => $this->name,
            'location_id' => $this->location_id,
            'location' => $this->whenLoaded('location'),
            'is_singleton' => $this->is_singleton,
            'is_blueprint_copy' => $this->is_blueprint_copy,
            'content' => $this::collection($this->whenLoaded('content')),
            'owner' => $this->whenLoaded('owner'),
        ];
    }

}
