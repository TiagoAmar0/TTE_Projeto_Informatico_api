<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shift_id' => $this->shift_id,
            'user_id' => $this->user_id,
            'date' => date('m/d', strtotime($this->date)),
            'user' => new UserResource($this->whenLoaded('user')),
            'shift' => new ShiftResource($this->whenLoaded('shift')),
        ];
    }
}
