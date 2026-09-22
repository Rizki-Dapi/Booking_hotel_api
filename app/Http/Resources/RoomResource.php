<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'room_number' => $this->room_number,
            'floor' => $this->floor,
            'status' => $this->status,
            'is_occupied' => $this->whenLoaded('bookings', fn() => $this->bookings->isNotEmpty()),
            'room_type' => $this->whenLoaded('roomType', fn() => [
                'id' => $this->roomType->id,
                'name' => $this->roomType->name,
                'price_per_night' => (float) $this->roomType->price_per_night,
                'capacity' => $this->roomType->capacity,
                'hotel' => $this->roomType->relationLoaded('hotel') ? [
                    'id' => $this->roomType->hotel->id,
                    'name' => $this->roomType->hotel->name,
                    'city' => $this->roomType->hotel->city,
                ] : null,
            ]),
        ];
    }
}
