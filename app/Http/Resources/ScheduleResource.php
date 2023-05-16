<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
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
            'start' => $this->start,
            'end' => $this->end,
            'draft' => $this->draft,
            'service_id' => $this->service_id,
            'shifts' => $this->whenLoaded('shifts', ShiftResource::collection($this->shifts()->get())),
            'users' => $this->whenLoaded('users', UserResource::collection($this->users()->orderBy('name')->get())),
            'user_shifts' => $this->whenLoaded('userShifts', ShiftUserResource::collection($this->userShifts)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
