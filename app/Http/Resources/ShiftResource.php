<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'start' =>  Carbon::createFromFormat('H:i:s', $this->start)->format('H:i'),
            'end' =>  Carbon::createFromFormat('H:i:s', $this->end)->format('H:i'),
            'hours' => sprintf("%02d:%02d", floor($this->minutes / 60), $this->minutes % 60),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'minutes' => $this->minutes,
            'nurses_qty' => $this->nurses_qty,
            'users' => UserResource::collection($this->whenLoaded('users')),
        ];
    }
}
