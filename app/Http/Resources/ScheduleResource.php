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
            'shifts' => $this->whenLoaded('service', ShiftResource::collection($this->service->shifts)),
            'users' => $this->whenLoaded('service', UserResource::collection($this->service->users)),
            'user_shifts' => $this->whenLoaded('userShifts', ShiftUserResource::collection($this->userShifts)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
