<?php

namespace App\Http\Resources;

use Carbon\Carbon;
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
        $carbon = Carbon::createFromFormat('Y-m-d', $this->date);

        return [
            'id' => $this->id,
            'shift_id' => $this->shift_id,
            'user_id' => $this->user_id,
            'date' => $carbon->format('d/m/Y'),
            'day_name' => ucfirst($carbon->shortDayName),
            'day' => $carbon->day,
            'month' => ucfirst($carbon->shortMonthName),
            'user' => new UserResource($this->whenLoaded('user')),
            'shift' => new ShiftResource($this->whenLoaded('shift')),
        ];
    }
}
