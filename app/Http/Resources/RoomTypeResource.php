<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price_per_night' => (float) $this->price_per_night,
            'capacity' => $this->capacity,
            'hotel' => $this->whenLoaded('hotel', fn() => [
                'id' => $this->hotel->id,
                'name' => $this->hotel->name,
                'city' => $this->hotel->city,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
