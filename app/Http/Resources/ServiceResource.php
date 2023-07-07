<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
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
            'shifts' => ShiftResource::collection($this->shifts),
            'users' => UserResource::collection(
                $this->users()
                ->orderBy('name', 'ASC')
                ->get()
            ),
            'serviceHasLead' => $this->users()->where('type', 'lead-nurse')->exists(),
            'nursesWithoutService' => UserResource::collection(
                User::query()
                    ->whereNull('service_id')
                    ->whereNot('type', 'admin')
                    ->orderBy('name')
                    ->get())
        ];
    }
}
