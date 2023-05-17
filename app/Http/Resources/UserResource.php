<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'email' => $this->email,
            'service' => $this->service ? $this->service->name : null,
            'service_id' => $this->service ? $this->service->id : null,
            'type' => $this->type,
            'shifts' => ShiftUserResource::collection($this->shiftUsers()->with('shift')->where('date', '>=', Carbon::now()->format('Y-m-d'))->orderBy('date')->get()),
            'type_normalized' => match ($this->type) {
                'admin' => 'Administrador',
                'lead-nurse' => 'Enfermeiro Chefe',
                'nurse' => 'Enfermeiro'
            }
        ];
    }
}
