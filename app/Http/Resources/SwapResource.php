<?php

namespace App\Http\Resources;

use App\Models\ShiftUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SwapResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $target_shift_user = $this->targetShiftUser;
        $target_shift_user->load(['shift']);

        $payment_shift_user = $this->paymentShiftUser;
        $payment_shift_user->load(['shift']);

        return  [
            'id' => $this->id,
            'proposing_user' => new UserResource($this->proposingUser),
            'target_user' => new UserResource($this->targetUser),
            'target_shift_user' => new ShiftUserResource($target_shift_user),
            'payment_shift_user' => new ShiftUserResource($payment_shift_user),
            'direct' => $this->direct,
            'status' => match ($this->status) {
                'pending' => 'Pendente',
                'approved' => 'Aprovado',
                'rejected' => 'Rejeitado'
            }
        ];
    }
}
